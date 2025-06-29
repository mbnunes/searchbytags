document.addEventListener('DOMContentLoaded', () => {
  const query = new URLSearchParams(window.location.search).get('query') || '';
  if (!query) return;

  fetch(OC.generateUrl('/apps/search_by_tags/api/search') + '?query=' + encodeURIComponent(query))
    .then(res => res.json())
    .then(data => {
      const grid = document.querySelector('.file-grid');
      if (!data.files || data.files.length === 0) {
        grid.innerHTML = '<p>Nenhum arquivo encontrado.</p>';
        return;
      }

      data.files.forEach(file => {
        const card = document.createElement('div');
        card.className = 'file-card';

        const thumb = document.createElement('img');
        thumb.className = 'file-thumb';
        thumb.src = OC.generateUrl(`/index.php/core/preview?fileId=${file.id}&x=100&y=100`);
        thumb.alt = file.name;

        const name = document.createElement('div');
        name.textContent = file.name;

        const link = document.createElement('a');
        link.href = file.url;
        link.appendChild(thumb);
        link.appendChild(name);

        card.appendChild(link);
        grid.appendChild(card);
      });
    });
});
