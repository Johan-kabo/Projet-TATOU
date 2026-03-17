// Basic JS interactions for the dashboard demo.

window.addEventListener('DOMContentLoaded', () => {
  // Prevent search inputs from submitting forms and enable client-side table filtering.
  const searchInputs = document.querySelectorAll('input[type=search]');

  searchInputs.forEach((input) => {
    input.addEventListener('keypress', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
      }
    });

    input.addEventListener('input', () => {
      const query = input.value.trim().toLowerCase();
      const tableSelector = input.dataset.filterTarget;
      if (!tableSelector) return;

      const table = document.querySelector(tableSelector);
      if (!table) return;

      const rows = table.querySelectorAll('tbody tr');
      rows.forEach((row) => {
        const text = row.textContent.toLowerCase();
        const match = text.includes(query);
        row.style.display = match ? '' : 'none';
      });
    });
  });
});
