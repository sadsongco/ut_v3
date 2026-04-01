const scrollToSection = (event) => {
  const target = document.getElementById(event.target.dataset.target);
  target.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'start' });
};
