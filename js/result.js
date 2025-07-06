document.addEventListener('DOMContentLoaded', async function () {
	const input = document.getElementById('tag-input');
	const tagResults = document.getElementById('tag-results');

	let debounceTimeout;
	let currentPage = 1;
	let perPage = 20;

	// Cria seletor de quantidade por página
	const perPageSelector = document.createElement('select');
	perPageSelector.innerHTML = `
		<option value="10">10</option>
		<option value="20" selected>20</option>
		<option value="50">50</option>
	`;
	perPageSelector.addEventListener('change', () => {
		perPage = parseInt(perPageSelector.value);
		currentPage = 1;
		loadResults(input.value.trim());
	});
	document.querySelector('.sidebar')?.appendChild(perPageSelector);

	// Paginação
	const paginationControls = document.createElement('div');
	paginationControls.style.marginTop = '1rem';
	paginationControls.style.display = 'flex';
	paginationControls.style.justifyContent = 'center';
	paginationControls.style.gap = '1rem';

	const prevButton = document.createElement('button');
	prevButton.textContent = '← Anterior';
	prevButton.onclick = () => {
		if (currentPage > 1) {
			currentPage--;
			loadResults(input.value.trim());
		}
	};

	const nextButton = document.createElement('button');
	nextButton.textContent = 'Próximo →';
	nextButton.onclick = () => {
		currentPage++;
		loadResults(input.value.trim());
	};

	paginationControls.appendChild(prevButton);
	paginationControls.appendChild(nextButton);
	document.querySelector('.sidebar')?.appendChild(paginationControls);

	input.addEventListener('input', function () {
		clearTimeout(debounceTimeout);

		debounceTimeout = setTimeout(async () => {
			const val = input.value.trim();
			if (val.length >= 2) {
				const tags = await fetchTags();
				const datalist = document.getElementById('tag-suggestions');
				datalist.innerHTML = '';
				tags.forEach(tag => {
					const opt = document.createElement('option');
					opt.value = tag.name;
					datalist.appendChild(opt);
				});
			}
			currentPage = 1;
			await loadResults(val);
			await renderTagFolders(val);
			history.replaceState(null, '', OC.generateUrl('/apps/search_by_tags/') + '?query=' + encodeURIComponent(val));
		}, 300);
	});

	const urlParams = new URLSearchParams(window.location.search);
	const query = urlParams.get('query');
	if (query) {
		input.value = query;
		await loadResults(query);
		await renderTagFolders(query);
	}

	async function fetchTags() {
		let allTags = [];

		try {
			const response = await fetch(OC.generateUrl('/apps/search_by_tags/search/getAllTags'), {
				headers: { 'OCS-APIREQUEST': 'true' },
				credentials: 'include'
			});

			const data = await response.json();

			if (data.tags) {
				allTags = data.tags;
			} else {
				console.warn('Resposta inesperada:', data);
			}
		} catch (err) {
			console.error('Erro ao buscar todas as tags:', err);
		}

		return allTags;
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

			const fileList = data.files.map(file => ({
				id: file.id,
				name: file.name,
				mime: file.mime || 'image/jpeg',
				path: file.path + '/' + file.name,
				size: file.size || 0,
				etag: file.etag || '',
				mtime: file.mtime || 0,
				permissions: 1,
				type: 'file',
				directory: file.path
			}));

			const start = (currentPage - 1) * perPage;
			const end = start + perPage;
			const paginatedFiles = fileList.slice(start, end);

			nextButton.disabled = end >= fileList.length;
			prevButton.disabled = currentPage <= 1;

			paginatedFiles.forEach((file, index) => {
				const item = document.createElement('div');
				item.className = 'file-card';

				const link = document.createElement('a');
				link.href = '#';
				link.className = 'file-link';
				link.addEventListener('click', (e) => {
					e.preventDefault();
					if (OCA?.Viewer?.open) {
						OCA.Viewer.open(fileList, start + index);
					}
				});

				const img = document.createElement('img');
				img.src = OC.generateUrl(`/core/preview?fileId=${file.id}&x=128&y=128`);
				img.alt = file.name;
				img.className = 'thumbnail';

				const name = document.createElement('div');
				name.className = 'filename';
				name.title = file.name;
				name.textContent = shortenFilename(file.name);

				const date = document.createElement('div');
				date.className = 'filedate';
				date.textContent = formatDate(file.mtime);

				link.appendChild(img);
				link.appendChild(name);
				link.appendChild(date);

				item.appendChild(link);
				tagResults.appendChild(item);
			});

		} catch (err) {
			console.error('Erro ao carregar arquivos:', err);
		}
	}

	async function renderTagFolders(query) {
		const tagFoldersContainer = document.getElementById('tag-folders');
		tagFoldersContainer.innerHTML = '';

		if (!query) return;

		const operators = ['and', 'or'];
		const tagParts = query
			.split(/\s+/)
			.map(t => t.trim())
			.filter(t => t && !operators.includes(t.toLowerCase()));

		if (tagParts.length === 0) return;

		let allTags = await fetchTags();

		tagParts.forEach(inputTag => {
			const match = allTags.find(tag => tag.name.toLowerCase() === inputTag.toLowerCase());
			if (match) {
				const tagLink = document.createElement('a');
				tagLink.className = 'tag-link';
				tagLink.href = OC.generateUrl(`/apps/files/tags/${match.id}?dir=/${match.id}`);

				const icon = document.createElement('span');
				icon.className = 'icon-tag';
				icon.textContent = '🏷️';

				const label = document.createElement('span');
				label.textContent = match.name;

				tagLink.appendChild(icon);
				tagLink.appendChild(label);
				tagFoldersContainer.appendChild(tagLink);
			}
		});
	}
});

function shortenFilename(filename, maxLength = 30) {
	if (filename.length <= maxLength) {
		return filename;
	}

	const extIndex = filename.lastIndexOf('.');
	const namePart = filename.substring(0, extIndex);
	const extPart = filename.substring(extIndex);

	const keep = Math.floor((maxLength - extPart.length - 3) / 2);
	return namePart.substring(0, keep) + '...' + namePart.substring(namePart.length - keep) + extPart;
}

function formatDate(timestamp) {
	if (!timestamp) return '';
	const date = new Date(timestamp * 1000);
	return date.toLocaleString('pt-BR', {
		day: '2-digit',
		month: '2-digit',
		year: 'numeric',
		hour: '2-digit',
		minute: '2-digit'
	});
}
