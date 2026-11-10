// Don't: this runs once, and never sees content added later.
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.views-row').forEach((row) => {
    row.classList.add('seen-by-script');
  });
});
