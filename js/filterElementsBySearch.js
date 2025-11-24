const filterElementsBySearch = (inputEl, targetEl) => {
  const input = document.getElementById(inputEl);
  const targetEls = document.querySelectorAll(targetEl);
  const noResults = document.querySelector('div.no-results-message');
  const value = input.value.toLowerCase();
  console.log(targetEl);

  targetEls.forEach((el) => {
    console.log(el);
    let elementText = '';

    const filterEls = el.querySelectorAll('.filterValue');

    if (filterEls.length > 0) {
      filterEls.forEach((filterEl) => {
        console.log(filterEl);
        elementText += filterEl.textContent.toLowerCase();
      });
    }

    const matches = elementText.indexOf(value) > -1;

    el.style.display = matches ? 'block' : 'none';

    // If an element doesn't match, and there's an element chosen to display a message
    if (matches && targetEls.length > 0) {
      noResults.style.display = 'none';
    } else {
      noResults.style.display = 'block';
    }
  });
};

const resetSearch = (inputEl, targetEl) => {
  const input = document.getElementById(inputEl);
  const targetEls = document.querySelectorAll(targetEl);
  const noResults = document.querySelector('div.no-results-message');

  input.value = '';

  noResults.style.display = 'none';

  targetEls.forEach((el) => {
    el.style.display = 'block';
  });
};
