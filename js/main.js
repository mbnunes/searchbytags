document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('search-query');
    const btn = document.getElementById('search-btn');
    const results = document.getElementById('results');

    btn.addEventListener('click', () => {
        const query = input.value.trim();
        if (!query) return;

        fetch(OC.generateUrl('/apps/search_by_tags/api/search?query=' + encodeURIComponent(query)))
            .then(resp => resp.json())
            .then(data => {
                results.innerHTML = '';
                if (!data.files || data.files.length === 0) {
                    results.innerHTML = '<li>Nenhum resultado encontrado</li>';
                    return;
                }
                data.files.forEach(file => {
                    const li = document.createElement('li');
                    const a = document.createElement('a');
                    a.href = file.url;
                    a.textContent = `${file.name} (${file.path})`;
                    a.target = '_blank';
                    li.appendChild(a);
                    results.appendChild(li);
                });
            })
            .catch(err => {
                results.innerHTML = '<li>Erro na busca</li>';
                console.error(err);
            });
    });
});
