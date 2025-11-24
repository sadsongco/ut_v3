const copyToClipboard = async (e, tag) => {
  e.preventDefault();
  e.stopPropagation();
  try {
    await navigator.clipboard.writeText(tag);
    e.target.innerHTML = 'Copied!';
  } catch (e) {
    console.error(e);
    throw new Error('failed to copy');
  }
};
