/**
 * Resize accordion item based on click event target
 * @param {Event} e event from click handler
 * @returns {Promise<void>}
 */
const resize = async (e) => {
  const item = document.getElementById(e.target.dataset.targetid);
  closeOpenAccordion(item.id);
  if (item.id !== 'blog') {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.delete('article_id');
    window.history.pushState({}, '', '/');
  }
  if (item.id === 'blog') {
    updateURL();
  }
  if (item.id === 'hero') {
    if (item.classList.contains('is-open')) setTimeout(stopCarousel, 500);
    else startCarousel();
  }
  await resizeAccordion(item);
};

/**
 * Handle accordion content height resize after Htmx event
 * @param {CustomEvent} e event from Htmx
 * @returns {Promise<void>}
 */
const resizeHTMX = async (e) => {
  const urlParams = new URLSearchParams(window.location.search);
  if (!urlParams.has('article_id')) return;
  if (e.target.classList.contains('blog') || e.target.classList.contains('audioPlayer') || e.target.classList.contains('comments-container') || e.target.classList.contains('commentReply')) {
    const item = document.getElementById('blog-content');
    item.style.maxHeight = `${item.scrollHeight}px`;
    return;
  }
  const item = e.detail.target;
  if (item.id === 'blog-content') {
    item.style.maxHeight = `${item.scrollHeight}px`;
    return;
  }
};

/**
 * Toggles the visibility of the accordion content associated with the given item.
 * Expands the accordion to show its content if it is currently collapsed, and collapses it otherwise.
 * Adjusts the CSS transition and padding styles to animate the expansion or collapse.
 * Updates the icon within the item's header to reflect the current state.
 *
 * @param {HTMLElement} item - The accordion item to be resized.
 * @returns {Promise<void>}
 */

const resizeAccordion = async (item) => {
  const target = document.getElementById(`${item.id}-content`);
  item.classList.toggle('is-open');
  if (item.classList.contains('is-open')) {
    // Scrollheight property return the height of
    // an element including padding
    target.style.transition = 'max-height 0.5s ease-in-out, padding 0s linear';
    target.style.padding = 'var(--stdPaddingSmall)';
    target.style.maxHeight = `${target.scrollHeight}px`;
    item.querySelector('i').classList.replace('fa-plus', 'fa-minus');
  } else {
    target.style.transition = 'max-height 0.5s ease-in-out, padding 0.5s ease-in-out';
    target.style.padding = '0px';
    target.style.maxHeight = '0px';
    item.querySelector('i').classList.replace('fa-minus', 'fa-plus');
  }
};

/**
 * Closes all open accordion items except the one with the given id.
 * @param {string} id - The id of the accordion item to be left open.
 * @returns {void}
 */
const closeOpenAccordion = (id) => {
  accordionNodeList.forEach((item) => {
    if (item.id === id) return;
    const target = document.getElementById(`${item.id}-content`);
    item.classList.remove('is-open');
    item.querySelector('i').classList.replace('fa-minus', 'fa-plus');
    target.style.transition = 'max-height 0.5s ease-in-out, padding 0.5s ease-in-out';
    target.style.maxHeight = '0px';
    target.style.padding = '0px';
  });
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

const accordionNodeList = document.querySelectorAll('.accordion');
const accordionContent = [];
accordionNodeList.forEach((item) => {
  accordionContent[item.id] = item;
  let header = item.querySelector('header');
  header.addEventListener('click', resize);
});

document.body.addEventListener('htmx:afterSettle', resizeHTMX);
// document.body.addEventListener('htmx:load', resizeHTMX);
window.onload = () => {
  if (!accordionContent['hero']) return;
  let showContent = 'hero';
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.has('article_id')) showContent = 'blog';
  if (showContent === 'hero') startCarousel();
  resizeAccordion(accordionContent[showContent]);
};
