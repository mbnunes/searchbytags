document.addEventListener('DOMContentLoaded', async function () {
	const input = document.getElementById('tag-input');
	const tagResults = document.getElementById('tag-results');

	input.addEventListener('input', async function () {
		const val = input.value.trim();
		if (val.length >= 2) {
			const tags = await fetchTags(val);
			const datalist = document.getElementById('tag-suggestions');
			datalist.innerHTML = '';
			tags.forEach(tag => {
				const opt = document.createElement('option');
				opt.value = tag.name;
				datalist.appendChild(opt);
			});
		}
		await loadResults(val);
		renderTagFolders(val);
		history.replaceState(null, '', OC.generateUrl('/apps/search_by_tags/') + '?query=' + encodeURIComponent(val));
	});

	const urlParams = new URLSearchParams(window.location.search);
	const query = urlParams.get('query');
	if (query) {
		input.value = query;
		await loadResults(val);
		renderTagFolders(val);
	}

	async function fetchTags(filter) {
		try {
			const response = await fetch(OC.generateUrl('/ocs/v2.php/apps/files/api/v1/tags?format=json'), {
				headers: { 'OCS-APIREQUEST': 'true' }
			});
			const data = await response.json();
			return data.ocs.data.filter(tag => tag.name.toLowerCase().includes(filter.toLowerCase()));
		} catch (err) {
			console.error('Erro ao buscar tags:', err);
			return [];
		}
	}

	async function loadResults(query) {
		try {
			const res = await fetch(OC.generateUrl('/apps/search_by_tags/api/search') + '?query=' + encodeURIComponent(query));
			const data = await res.json();

			tagResults.innerHTML = '';

			if (!data.files || data.files.length === 0) {
				tagResults.innerHTML = '<div class="item">Nenhum arquivo encontrado.</div>';
				return;
			}

			const fileList = [];

			data.files.forEach(file => {
				fileList.push({
					id: file.id,
					name: file.name,
					mime: file.mime || 'image/jpeg',
					path: file.path + '/' + file.name,
					size: file.size || 0,
					etag: file.etag || '',
					permissions: 1,
					type: 'file',
					directory: file.path
				});
			});

			data.files.forEach((file, index) => {
				const item = document.createElement('div');
				item.className = 'item';

				const link = document.createElement('a');
				link.href = '#';
				link.addEventListener('click', (e) => {
					e.preventDefault();
					if (OCA?.Viewer?.open) {
						OCA.Viewer.open(fileList, index);
					}
				});

				const img = document.createElement('img');
				img.src = OC.generateUrl(`/core/preview?fileId=${file.id}&x=128&y=128`);
				img.alt = file.name;
				img.className = 'thumbnail';

				const name = document.createElement('div');
				name.className = 'filename';
				name.textContent = file.name;

				link.appendChild(img);
				link.appendChild(name);
				item.appendChild(link);
				tagResults.appendChild(item);
			});

		} catch (err) {
			console.error('Erro ao carregar arquivos:', err);
		}
	}

	function renderTagFolders(query) {
		const tagFoldersContainer = document.getElementById('tag-folders');
		tagFoldersContainer.innerHTML = ''; // Limpa conteúdo anterior

		if (!query) return;

		const tagParts = query.split(/\s+(AND|OR)\s+/i).filter(t => t !== 'AND' && t !== 'OR');

		tagParts.forEach(tag => {
			const tagDiv = document.createElement('div');
			tagDiv.className = 'app-navigation-entry';
			tagDiv.textContent = tag;
			tagFoldersContainer.appendChild(tagDiv);
		});
	}
});
