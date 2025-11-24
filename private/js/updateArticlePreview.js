const updateArticlePreview = () => {
  htmx.trigger('#articleBody', 'updatePreview');
};
