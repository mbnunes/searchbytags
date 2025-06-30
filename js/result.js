document.addEventListener('DOMContentLoaded', function () {
    const tagResults = document.getElementById('tag-results');
    if (!tagResults) return;

    const urlParams = new URLSearchParams(window.location.search);
    const query = urlParams.get('query');
    if (!query) {
        tagResults.innerHTML = '<p>Nenhuma tag fornecida.</p>';
        return;
    }

    fetch(OC.generateUrl('/apps/search_by_tags/api/search?query=') + encodeURIComponent(query))
        .then(response => response.json())
        .then(data => {
            if (!data.files || data.files.length === 0) {
                tagResults.innerHTML = '<p>Nenhum arquivo encontrado com essas tags.</p>';
                return;
            }

            const container = document.createElement('ul');
            container.className = 'fileListView';
            container.style.display = 'flex';
            container.style.flexWrap = 'wrap';
            container.style.gap = '1.5em';

            data.files.forEach(file => {
                const item = document.createElement('li');
                item.className = 'file';
                item.style.listStyle = 'none';
                item.style.width = '160px';

                const link = document.createElement('a');
                link.href = OC.generateUrl('/apps/files') + '?dir=' + encodeURIComponent(file.path) + '&scrollto=' + encodeURIComponent(file.name);
                link.className = 'filename';
                link.style.display = 'block';
                link.style.textAlign = 'center';

                const thumb = document.createElement('img');
                thumb.src = OC.generateUrl(`/apps/files/api/v1/thumbnail/${file.fileid}/256`);
                thumb.alt = file.name;
                thumb.className = 'thumbnail';
                thumb.style.width = '100%';
                thumb.style.borderRadius = '8px';

                const title = document.createElement('div');
                title.textContent = file.name;
                title.style.marginTop = '0.5em';
                title.style.fontSize = '0.9em';
                title.style.wordBreak = 'break-word';

                link.appendChild(thumb);
                link.appendChild(title);
                item.appendChild(link);
                container.appendChild(item);
            });

            tagResults.innerHTML = '';
            tagResults.appendChild(container);
        })
        .catch(error => {
            console.error('Erro ao buscar arquivos por tag:', error);
            tagResults.innerHTML = '<p>Erro ao buscar arquivos.</p>';
        });
});
