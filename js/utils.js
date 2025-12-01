const disableButton = (e) => {
  console.log(e.target);
  e.target.disabled = true;
  e.target.classList.add('disabled');
};

/**
 * Updates the browser's URL to reflect the current article being viewed.
 * If an event is provided, it uses the article ID from the event's target dataset.
 * Otherwise, it defaults to the article ID from the 'blog-content' element.
 * This change is made without reloading the page by using the History API.
 *
 * @param {Event|boolean} e - The event from the click handler or false if no event.
 */

const updateURL = (e = false) => {
  let article_id;
  if (!e) {
    article_id = parseInt(document.getElementById('blog-content').dataset.article_id);
  } else {
    article_id = parseInt(e.target.dataset.article_id);
  }
  const urlParams = new URLSearchParams(window.location.search);
  urlParams.set('article_id', article_id);
  window.history.pushState({}, '', `?${urlParams.toString()}`);
};
