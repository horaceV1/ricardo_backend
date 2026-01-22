(function (Drupal) {
  'use strict';

  Drupal.behaviors.dynamicFormPreview = {
    attach: function (context, settings) {
      // Adicionar funcionalidade aos botões de upload
      const uploadWrappers = context.querySelectorAll('.file-upload-wrapper');
      
      uploadWrappers.forEach(function(wrapper) {
        const button = wrapper.querySelector('.file-upload-button');
        const input = wrapper.querySelector('.file-upload-input');
        const fileName = wrapper.querySelector('.file-upload-name');
        
        if (button && input && fileName) {
          // Prevenir múltiplos event listeners
          if (button.dataset.initialized) {
            return;
          }
          button.dataset.initialized = 'true';
          
          // Quando o botão é clicado, ativa o input file
          button.addEventListener('click', function(e) {
            e.preventDefault();
            input.click();
          });
          
          // Quando um ficheiro é selecionado, mostra o nome
          input.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
              fileName.textContent = this.files[0].name;
              fileName.classList.add('file-selected');
            } else {
              fileName.textContent = Drupal.t('Nenhum ficheiro selecionado');
              fileName.classList.remove('file-selected');
            }
          });
        }
      });
    }
  };

})(Drupal);
