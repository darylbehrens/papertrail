document.addEventListener('DOMContentLoaded', () => {
  const owlSelect = document.getElementById('owl_species');
  const lookupBtn = document.getElementById('lookup-owl');
  const sidePanel = document.getElementById('owl_side_panel');
  const closeBtn = document.getElementById('close_owl_panel');
  const spinner = document.getElementById('owl_panel_spinner');
  const contentBox = document.getElementById('owl_panel_content');
  const img = document.getElementById('owl_panel_img');
  const summary = document.getElementById('owl_panel_summary');
  const link = document.getElementById('owl_panel_link');

  if (!owlSelect || !lookupBtn || !sidePanel) return;

  lookupBtn.addEventListener('click', async () => {
    const species = owlSelect.value;
    if (!species) return;

    img.src = '';
    summary.textContent = '';
    link.href = '#';
    link.textContent = '';
    spinner.style.display = 'block';
    contentBox.style.display = 'none';
    sidePanel.classList.add('open');

    try {
      const url = `https://en.wikipedia.org/api/rest_v1/page/summary/${encodeURIComponent(species)}`;
      const response = await fetch(url);
      if (!response.ok) throw new Error('Wiki fetch failed');
      const data = await response.json();

      if (data.thumbnail?.source) {
        img.src = data.thumbnail.source;
        img.alt = species;
      }

      summary.textContent = data.extract;
      link.href = data.content_urls.desktop.page;
      link.textContent = 'Read more on Wikipedia';

      contentBox.style.display = 'block';
    } catch (err) {
      summary.textContent = 'No information found for this owl.';
      contentBox.style.display = 'block';
    } finally {
      spinner.style.display = 'none';
    }
  });

  const pageWrapper = document.getElementById('page_wrapper');

  if (pageWrapper) {
    lookupBtn.addEventListener('click', () => {
      pageWrapper.classList.add('panel-open');
    });

    closeBtn.addEventListener('click', () => {
      sidePanel.classList.remove('open');
      pageWrapper.classList.remove('panel-open');
    });
  } else {
    // fallback: still close the panel if wrapper is missing
    closeBtn.addEventListener('click', () => {
      sidePanel.classList.remove('open');
    });
  }
});