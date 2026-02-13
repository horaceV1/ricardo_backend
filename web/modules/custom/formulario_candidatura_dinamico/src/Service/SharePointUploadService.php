<?php

namespace Drupal\formulario_candidatura_dinamico\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\ClientInterface;

/**
 * Service to upload files to SharePoint via Microsoft Graph API.
 *
 * Uses the SharePoint Integration module's configuration (tenant_id,
 * application_id, client_secret) to authenticate and upload documents
 * submitted through dynamic forms to a specific SharePoint site and folder.
 */
class SharePointUploadService {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Microsoft Graph API base URL.
   */
  const GRAPH_API_BASE_URL = 'https://graph.microsoft.com/v1.0';

  /**
   * The SharePoint site name to upload to.
   */
  const SHAREPOINT_SITE_NAME = 'Clinica do Empresario';

  /**
   * The folder name within the document library to upload to.
   */
  const SHAREPOINT_FOLDER_NAME = 'Documentos';

  /**
   * Allowed file extensions for upload.
   */
  const ALLOWED_EXTENSIONS = ['pdf', 'png', 'jpg', 'jpeg'];

  /**
   * Constructs a new SharePointUploadService.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    ClientInterface $http_client,
    ConfigFactoryInterface $config_factory,
    FileSystemInterface $file_system,
    LoggerInterface $logger
  ) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->fileSystem = $file_system;
    $this->logger = $logger;
  }

  /**
   * Uploads a Drupal file entity to SharePoint.
   *
   * @param \Drupal\file\Entity\File $file
   *   The file entity to upload.
   * @param string $form_id
   *   The dynamic form ID (used for subfolder organization).
   * @param string $submitter_email
   *   The email of the person who submitted the form.
   *
   * @return bool
   *   TRUE if the upload was successful, FALSE otherwise.
   */
  public function uploadFileToSharePoint($file, string $form_id, string $submitter_email): bool {
    try {
      // Check if the file extension is allowed.
      $filename = $file->getFilename();
      $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

      if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
        $this->logger->info('Skipping SharePoint upload for @file: extension @ext not in allowed list.', [
          '@file' => $filename,
          '@ext' => $extension,
        ]);
        return FALSE;
      }

      // Get the access token.
      $token = $this->getAccessToken();
      if (!$token) {
        $this->logger->error('SharePoint upload failed: could not obtain access token.');
        return FALSE;
      }

      // Find the target site.
      $siteId = $this->findSiteId($token);
      if (!$siteId) {
        $this->logger->error('SharePoint upload failed: could not find site "@site".', [
          '@site' => self::SHAREPOINT_SITE_NAME,
        ]);
        return FALSE;
      }

      // Find the default document library drive for the site.
      $driveId = $this->findDriveId($token, $siteId);
      if (!$driveId) {
        $this->logger->error('SharePoint upload failed: could not find document library for site.');
        return FALSE;
      }

      // Find or create the "Documentos" folder.
      $folderId = $this->findOrCreateFolder($token, $driveId, 'root', self::SHAREPOINT_FOLDER_NAME);
      if (!$folderId) {
        $this->logger->error('SharePoint upload failed: could not find or create folder "@folder".', [
          '@folder' => self::SHAREPOINT_FOLDER_NAME,
        ]);
        return FALSE;
      }

      // Create a subfolder based on form_id and submission date for organization.
      $subfolderName = $form_id . '_' . date('Y-m');
      $subfolderId = $this->findOrCreateFolder($token, $driveId, $folderId, $subfolderName);
      $targetFolderId = $subfolderId ?: $folderId;

      // Prefix filename with timestamp and submitter email for uniqueness.
      $safeEmail = preg_replace('/[^a-zA-Z0-9]/', '_', $submitter_email);
      $uploadFilename = date('Ymd_His') . '_' . $safeEmail . '_' . $filename;

      // Read the file content.
      $fileUri = $file->getFileUri();
      $realPath = $this->fileSystem->realpath($fileUri);

      if (!$realPath || !file_exists($realPath)) {
        $this->logger->error('SharePoint upload failed: file not found at @uri.', [
          '@uri' => $fileUri,
        ]);
        return FALSE;
      }

      $fileContent = file_get_contents($realPath);
      $fileSize = filesize($realPath);

      if ($fileSize === 0) {
        $this->logger->error('SharePoint upload failed: file @file is empty.', [
          '@file' => $filename,
        ]);
        return FALSE;
      }

      // Upload file (use simple upload for files under 4MB, session for larger).
      if ($fileSize < 4 * 1024 * 1024) {
        $result = $this->uploadSmallFile($token, $driveId, $targetFolderId, $uploadFilename, $fileContent);
      }
      else {
        $result = $this->uploadLargeFile($token, $driveId, $targetFolderId, $uploadFilename, $realPath, $fileSize);
      }

      if ($result) {
        $this->logger->info('Successfully uploaded @file to SharePoint (site: @site, folder: @folder/@subfolder).', [
          '@file' => $filename,
          '@site' => self::SHAREPOINT_SITE_NAME,
          '@folder' => self::SHAREPOINT_FOLDER_NAME,
          '@subfolder' => $subfolderName,
        ]);
        return TRUE;
      }

      $this->logger->error('SharePoint upload failed for @file: upload returned empty result.', [
        '@file' => $filename,
      ]);
      return FALSE;

    }
    catch (\Exception $e) {
      $this->logger->error('SharePoint upload exception for @file: @message', [
        '@file' => $file->getFilename(),
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Gets an access token using the SharePoint Integration module config.
   *
   * @return string|null
   *   The access token, or NULL on failure.
   */
  protected function getAccessToken(): ?string {
    try {
      $config = $this->configFactory->get('sharepoint_integration.settings');
      $tenantId = $config->get('tenant_id');
      $clientId = $config->get('application_id');
      $clientSecret = $config->get('client_secret');

      if (empty($tenantId) || empty($clientId) || empty($clientSecret)) {
        $this->logger->error('SharePoint Integration module is not fully configured (missing tenant_id, application_id, or client_secret).');
        return NULL;
      }

      $tokenUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";
      $response = $this->httpClient->post($tokenUrl, [
        'form_params' => [
          'grant_type' => 'client_credentials',
          'client_id' => $clientId,
          'client_secret' => $clientSecret,
          'scope' => 'https://graph.microsoft.com/.default',
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      return $data['access_token'] ?? NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get SharePoint access token: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Finds the SharePoint site ID by name.
   *
   * @param string $token
   *   The access token.
   *
   * @return string|null
   *   The site ID, or NULL if not found.
   */
  protected function findSiteId(string $token): ?string {
    try {
      $response = $this->httpClient->get(self::GRAPH_API_BASE_URL . '/sites?search=' . rawurlencode(self::SHAREPOINT_SITE_NAME) . '&$select=id,displayName', [
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
          'Accept' => 'application/json',
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      $sites = $data['value'] ?? [];

      foreach ($sites as $site) {
        // Match by display name (case-insensitive).
        if (isset($site['displayName']) && stripos($site['displayName'], 'Clinica') !== FALSE) {
          $this->logger->info('Found SharePoint site: @name (ID: @id)', [
            '@name' => $site['displayName'],
            '@id' => $site['id'],
          ]);
          return $site['id'];
        }
      }

      // Log available sites for debugging.
      $siteNames = array_map(function ($s) {
        return $s['displayName'] ?? 'unknown';
      }, $sites);
      $this->logger->warning('SharePoint site "@name" not found. Available sites: @sites', [
        '@name' => self::SHAREPOINT_SITE_NAME,
        '@sites' => implode(', ', $siteNames),
      ]);

      return NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Error searching for SharePoint site: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Finds the default document library drive for a site.
   *
   * @param string $token
   *   The access token.
   * @param string $siteId
   *   The site ID.
   *
   * @return string|null
   *   The drive ID, or NULL if not found.
   */
  protected function findDriveId(string $token, string $siteId): ?string {
    try {
      $response = $this->httpClient->get(self::GRAPH_API_BASE_URL . "/sites/{$siteId}/drives?\$select=id,name,driveType", [
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
          'Accept' => 'application/json',
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      $drives = $data['value'] ?? [];

      // Return the first documentLibrary type drive (usually "Documents" / "Documentos").
      foreach ($drives as $drive) {
        if (($drive['driveType'] ?? '') === 'documentLibrary') {
          $this->logger->info('Found document library: @name (ID: @id)', [
            '@name' => $drive['name'],
            '@id' => $drive['id'],
          ]);
          return $drive['id'];
        }
      }

      // Fallback: return first drive.
      if (!empty($drives)) {
        return $drives[0]['id'];
      }

      return NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Error finding drive for site: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Finds or creates a folder in a drive.
   *
   * @param string $token
   *   The access token.
   * @param string $driveId
   *   The drive ID.
   * @param string $parentFolderId
   *   The parent folder ID ('root' for root).
   * @param string $folderName
   *   The folder name.
   *
   * @return string|null
   *   The folder ID, or NULL on failure.
   */
  protected function findOrCreateFolder(string $token, string $driveId, string $parentFolderId, string $folderName): ?string {
    try {
      // First, try to find the existing folder.
      if ($parentFolderId === 'root') {
        $endpoint = "/drives/{$driveId}/root/children";
      }
      else {
        $endpoint = "/drives/{$driveId}/items/{$parentFolderId}/children";
      }

      $response = $this->httpClient->get(self::GRAPH_API_BASE_URL . $endpoint . '?$filter=name eq \'' . rawurlencode($folderName) . '\'&$select=id,name,folder', [
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
          'Accept' => 'application/json',
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      $items = $data['value'] ?? [];

      foreach ($items as $item) {
        if (isset($item['folder']) && strcasecmp($item['name'], $folderName) === 0) {
          return $item['id'];
        }
      }

      // Folder not found — create it.
      $createResponse = $this->httpClient->post(self::GRAPH_API_BASE_URL . $endpoint, [
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'name' => $folderName,
          'folder' => new \stdClass(),
          '@microsoft.graph.conflictBehavior' => 'fail',
        ],
      ]);

      $createdFolder = json_decode($createResponse->getBody()->getContents(), TRUE);
      $this->logger->info('Created SharePoint folder: @name (ID: @id)', [
        '@name' => $folderName,
        '@id' => $createdFolder['id'] ?? 'unknown',
      ]);

      return $createdFolder['id'] ?? NULL;
    }
    catch (\Exception $e) {
      // If creation failed with conflict, the folder might have been created
      // concurrently. Try to find it again.
      if (strpos($e->getMessage(), '409') !== FALSE || strpos($e->getMessage(), 'nameAlreadyExists') !== FALSE) {
        try {
          if ($parentFolderId === 'root') {
            $endpoint = "/drives/{$driveId}/root/children";
          }
          else {
            $endpoint = "/drives/{$driveId}/items/{$parentFolderId}/children";
          }

          $response = $this->httpClient->get(self::GRAPH_API_BASE_URL . $endpoint . '?$select=id,name,folder', [
            'headers' => [
              'Authorization' => 'Bearer ' . $token,
              'Accept' => 'application/json',
            ],
          ]);

          $data = json_decode($response->getBody()->getContents(), TRUE);
          foreach (($data['value'] ?? []) as $item) {
            if (isset($item['folder']) && strcasecmp($item['name'], $folderName) === 0) {
              return $item['id'];
            }
          }
        }
        catch (\Exception $retryException) {
          // Fall through to error log below.
        }
      }

      $this->logger->error('Error finding/creating folder "@name": @message', [
        '@name' => $folderName,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Uploads a small file (under 4MB) to SharePoint.
   *
   * @param string $token
   *   The access token.
   * @param string $driveId
   *   The drive ID.
   * @param string $parentFolderId
   *   The parent folder ID.
   * @param string $fileName
   *   The file name.
   * @param string $fileContent
   *   The binary file content.
   *
   * @return array|null
   *   The created drive item, or NULL on failure.
   */
  protected function uploadSmallFile(string $token, string $driveId, string $parentFolderId, string $fileName, string $fileContent): ?array {
    try {
      $encodedFileName = rawurlencode($fileName);
      $endpoint = self::GRAPH_API_BASE_URL . "/drives/{$driveId}/items/{$parentFolderId}:/{$encodedFileName}:/content";

      $response = $this->httpClient->put($endpoint, [
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
          'Content-Type' => 'application/octet-stream',
        ],
        'body' => $fileContent,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      return !empty($data['id']) ? $data : NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Small file upload failed for @file: @message', [
        '@file' => $fileName,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Uploads a large file (over 4MB) using an upload session.
   *
   * @param string $token
   *   The access token.
   * @param string $driveId
   *   The drive ID.
   * @param string $parentFolderId
   *   The parent folder ID.
   * @param string $fileName
   *   The file name.
   * @param string $filePath
   *   The local file path.
   * @param int $fileSize
   *   The file size in bytes.
   *
   * @return array|null
   *   The created drive item, or NULL on failure.
   */
  protected function uploadLargeFile(string $token, string $driveId, string $parentFolderId, string $fileName, string $filePath, int $fileSize): ?array {
    try {
      // Create upload session.
      $encodedFileName = rawurlencode($fileName);
      $endpoint = self::GRAPH_API_BASE_URL . "/drives/{$driveId}/items/{$parentFolderId}:/{$encodedFileName}:/createUploadSession";

      $sessionResponse = $this->httpClient->post($endpoint, [
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'item' => [
            '@microsoft.graph.conflictBehavior' => 'rename',
          ],
        ],
      ]);

      $sessionData = json_decode($sessionResponse->getBody()->getContents(), TRUE);
      $uploadUrl = $sessionData['uploadUrl'] ?? NULL;

      if (!$uploadUrl) {
        $this->logger->error('Failed to create upload session for @file.', [
          '@file' => $fileName,
        ]);
        return NULL;
      }

      // Upload in 3.2MB chunks.
      $chunkSize = 3200000;
      $handle = fopen($filePath, 'rb');
      $offset = 0;
      $result = NULL;

      while ($offset < $fileSize) {
        $length = min($chunkSize, $fileSize - $offset);
        $chunk = fread($handle, $length);
        $rangeEnd = $offset + $length - 1;

        $chunkResponse = $this->httpClient->put($uploadUrl, [
          'headers' => [
            'Content-Length' => $length,
            'Content-Range' => "bytes {$offset}-{$rangeEnd}/{$fileSize}",
          ],
          'body' => $chunk,
        ]);

        $result = json_decode($chunkResponse->getBody()->getContents(), TRUE);
        $offset += $length;
      }

      fclose($handle);
      return !empty($result['id']) ? $result : NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Large file upload failed for @file: @message', [
        '@file' => $fileName,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
