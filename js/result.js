document.addEventListener('DOMContentLoaded', function () {
    const tagResults = document.getElementById('tag-results');
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

            const container = document.createElement('div');
            container.style.display = 'flex';
            container.style.flexWrap = 'wrap';
            container.style.gap = '1em';

            data.files.forEach(file => {
                const fileCard = document.createElement('div');
                fileCard.style.width = '150px';
                fileCard.style.border = '1px solid #ccc';
                fileCard.style.borderRadius = '8px';
                fileCard.style.overflow = 'hidden';
                fileCard.style.textAlign = 'center';
                fileCard.style.background = '#fff';
                fileCard.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';

                const thumb = document.createElement('img');
                thumb.src = OC.generateUrl(`/apps/files/api/v1/thumbnail/${file.fileid}/256`);
                thumb.alt = file.name;
                thumb.style.width = '100%';

                const title = document.createElement('div');
                title.textContent = file.name;
                title.style.padding = '0.5em';
                title.style.fontSize = '0.9em';
                title.style.wordBreak = 'break-word';

                fileCard.appendChild(thumb);
                fileCard.appendChild(title);

                container.appendChild(fileCard);
            });

            tagResults.innerHTML = '';
            tagResults.appendChild(container);
        })
        .catch(error => {
            console.error('Erro ao buscar arquivos por tag:', error);
            tagResults.innerHTML = '<p>Erro ao buscar arquivos.</p>';
        });
});
