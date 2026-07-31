(() => {
  const isLocalPreview = ['localhost', '127.0.0.1'].includes(window.location.hostname);
  const localFriendlyUrls = {
    '/cliente.php': '/portal-cliente',
    '/cadastro.php': '/portal-cliente/cadastro',
    '/login.php': '/portal-cliente/entrar',
    '/recuperar-senha.php': '/portal-cliente/recuperar-senha',
    '/redefinir-senha.php': '/portal-cliente/redefinir-senha',
    '/admin/index.php': '/admin',
    '/admin/login.php': '/admin/entrar',
    '/admin/': '/admin',
    '/admin/entrar/': '/admin/entrar',
  };
  const localFormActions = {
    '/portal-cliente/cadastrar': '/registrar.php',
    '/portal-cliente/autenticar': '/autenticar.php',
    '/portal-cliente/chamados': '/abrir-chamado.php',
    '/portal-cliente/solicitar-redefinicao': '/solicitar-redefinicao.php',
    '/portal-cliente/confirmar-redefinicao': '/confirmar-redefinicao.php',
    '/admin/usuarios': '/admin/atualizar-usuario.php',
    '/admin/chamados': '/admin/atualizar-chamado.php',
  };

  if (isLocalPreview) {
    const friendlyUrl = localFriendlyUrls[window.location.pathname];
    if (friendlyUrl) window.history.replaceState({}, '', friendlyUrl + window.location.search + window.location.hash);

    document.querySelectorAll('form[action]').forEach((form) => {
      const action = form.getAttribute('action');
      if (action && localFormActions[action]) form.setAttribute('action', localFormActions[action]);
    });
  }

  const phone = document.querySelector('#phone');

  const formatPhone = (value) => {
    const digits = value.replace(/\D/g, '').slice(0, 11);
    if (digits.length <= 2) return digits ? `(${digits}` : '';
    if (digits.length <= 6) return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
    if (digits.length <= 10) return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
    return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
  };

  if (phone) phone.addEventListener('input', () => { phone.value = formatPhone(phone.value); });

  document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const currentField = document.getElementById(button.dataset.passwordToggle);
      if (!currentField) return;
      const shouldShow = currentField.type === 'password';
      document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        const field = document.getElementById(toggle.dataset.passwordToggle);
        if (field) field.type = shouldShow ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', String(shouldShow));
        toggle.setAttribute('aria-label', shouldShow ? 'Ocultar senhas' : 'Mostrar senhas');
      });
    });
  });

  const confirmModal = document.getElementById('portal-confirm-modal');
  const confirmModalMessage = document.getElementById('portal-confirm-message');
  const confirmModalTitle = document.getElementById('portal-confirm-title');
  const confirmModalAction = document.getElementById('portal-confirm-action');
  let pendingConfirmationButton = null;

  const closeConfirmModal = () => {
    if (!confirmModal) return;
    confirmModal.hidden = true;
    confirmModal.setAttribute('aria-hidden', 'true');
    pendingConfirmationButton?.focus();
    pendingConfirmationButton = null;
  };

  if (confirmModal && confirmModalMessage && confirmModalAction) {
    document.querySelectorAll('form [data-confirm]').forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        pendingConfirmationButton = button;
        confirmModalMessage.textContent = button.dataset.confirm || '';
        if (confirmModalTitle) confirmModalTitle.textContent = button.dataset.confirmTitle || 'Confirmar ação?';
        confirmModalAction.textContent = button.dataset.confirmAction || 'Confirmar';
        confirmModal.hidden = false;
        confirmModal.setAttribute('aria-hidden', 'false');
        confirmModalAction.focus();
      });
    });

    confirmModal.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', closeConfirmModal));
    confirmModal.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeConfirmModal(); });

    confirmModalAction.addEventListener('click', () => {
      const button = pendingConfirmationButton;
      const form = button?.closest('form');
      if (!button || !form) return closeConfirmModal();
      pendingConfirmationButton = null;
      confirmModal.hidden = true;
      confirmModal.setAttribute('aria-hidden', 'true');
      form.requestSubmit(button);
    });
  }

  const adminTabs = [...document.querySelectorAll('[data-admin-tab]')];
  if (adminTabs.length) {
    const activateAdminTab = (target, updateHash = false) => {
      const panel = document.getElementById(target);
      if (!panel) return;
      adminTabs.forEach((tab) => {
        const selected = tab.dataset.adminTab === target;
        tab.setAttribute('aria-selected', String(selected));
        tab.tabIndex = selected ? 0 : -1;
        const controlledPanel = document.getElementById(tab.getAttribute('aria-controls'));
        if (controlledPanel) controlledPanel.hidden = !selected;
      });
      if (updateHash) window.history.replaceState({}, '', `#${target}`);
    };
    const requestedTab = window.location.hash.slice(1);
    activateAdminTab(adminTabs.some((tab) => tab.dataset.adminTab === requestedTab) ? requestedTab : 'cadastros');
    adminTabs.forEach((tab, index) => {
      tab.addEventListener('click', () => activateAdminTab(tab.dataset.adminTab, true));
      tab.addEventListener('keydown', (event) => {
        if (!['ArrowRight', 'ArrowLeft', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        const nextIndex = event.key === 'Home' ? 0 : event.key === 'End' ? adminTabs.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + adminTabs.length) % adminTabs.length;
        adminTabs[nextIndex].focus();
        activateAdminTab(adminTabs[nextIndex].dataset.adminTab, true);
      });
    });
  }

  const ticketTabs = [...document.querySelectorAll('[data-ticket-tab]')];
  if (ticketTabs.length) {
    const activateTicketTab = (target) => ticketTabs.forEach((tab) => {
      const selected = tab.dataset.ticketTab === target;
      tab.setAttribute('aria-selected', String(selected));
      tab.tabIndex = selected ? 0 : -1;
      const panel = document.getElementById(tab.getAttribute('aria-controls'));
      if (panel) panel.hidden = !selected;
    });
    ticketTabs.forEach((tab) => tab.addEventListener('click', () => activateTicketTab(tab.dataset.ticketTab)));
  }

  const ticketDetailModal = document.getElementById('ticket-detail-modal');
  if (ticketDetailModal) {
    const title = document.getElementById('ticket-detail-title');
    const content = document.getElementById('ticket-detail-content');
    const close = () => { ticketDetailModal.hidden = true; ticketDetailModal.setAttribute('aria-hidden', 'true'); };
    const open = (row) => {
      let ticket;
      try { ticket = JSON.parse(row.dataset.ticketDetail || '{}'); } catch { return; }
      title.textContent = ticket.number || 'Chamado';
      content.replaceChildren();
      [['Cliente', ticket.client], ['E-mail', ticket.email], ['Empreendimento / obra', ticket.development], ['Unidade / bloco', ticket.unit || 'Não informado'], ['Sistema', ticket.system], ['Prioridade', ticket.priority], ['Solicitado em', ticket.createdAt], ['Descrição', ticket.description]].forEach(([label, value]) => {
        const term = document.createElement('dt'); term.textContent = label;
        const detail = document.createElement('dd'); detail.textContent = value || 'Não informado';
        content.append(term, detail);
      });
      ticketDetailModal.hidden = false; ticketDetailModal.setAttribute('aria-hidden', 'false');
    };
    document.querySelectorAll('[data-ticket-detail]').forEach((row) => {
      row.addEventListener('click', (event) => { if (!event.target.closest('button,form')) open(row); });
      row.addEventListener('keydown', (event) => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); open(row); } });
    });
    ticketDetailModal.querySelectorAll('[data-ticket-detail-close]').forEach((button) => button.addEventListener('click', close));
    ticketDetailModal.addEventListener('keydown', (event) => { if (event.key === 'Escape') close(); });
  }

  document.querySelectorAll('[data-datatable]').forEach((tableRoot) => {
    const tbody = tableRoot.querySelector('tbody');
    const search = tableRoot.querySelector('[data-dt-search]');
    const pageSizeSelect = tableRoot.querySelector('[data-dt-page-size]');
    const summary = tableRoot.querySelector('[data-dt-summary]');
    const pagination = tableRoot.querySelector('[data-dt-pagination]');
    const sortButtons = [...tableRoot.querySelectorAll('[data-dt-sort]')];
    if (!tbody || !search || !pageSizeSelect || !summary || !pagination) return;

    const rows = [...tbody.rows];
    let page = 1;
    let pageSize = Number(pageSizeSelect.value) || 10;
    let sortIndex = -1;
    let sortDirection = 1;

    const cellValue = (row, index) => row.cells[index]?.dataset.sort || row.cells[index]?.innerText || '';
    const render = () => {
      const query = search.value.trim().toLocaleLowerCase('pt-BR');
      const visibleRows = rows.filter((row) => row.innerText.toLocaleLowerCase('pt-BR').includes(query));
      if (sortIndex >= 0) visibleRows.sort((a, b) => cellValue(a, sortIndex).localeCompare(cellValue(b, sortIndex), 'pt-BR', { numeric: true, sensitivity: 'base' }) * sortDirection);
      const totalPages = Math.max(1, Math.ceil(visibleRows.length / pageSize));
      if (page > totalPages) page = totalPages;
      const start = (page - 1) * pageSize;
      rows.forEach((row) => { row.hidden = true; });
      visibleRows.slice(start, start + pageSize).forEach((row) => { row.hidden = false; tbody.appendChild(row); });
      summary.textContent = visibleRows.length ? `Exibindo ${start + 1}–${Math.min(start + pageSize, visibleRows.length)} de ${visibleRows.length}` : 'Nenhum resultado encontrado';
      pagination.replaceChildren();
      const makePageButton = (label, target, disabled = false, current = false) => {
        const button = document.createElement('button');
        button.type = 'button'; button.textContent = label; button.disabled = disabled;
        if (current) button.setAttribute('aria-current', 'page');
        button.addEventListener('click', () => { page = target; render(); });
        pagination.appendChild(button);
      };
      makePageButton('‹', Math.max(1, page - 1), page === 1);
      for (let current = Math.max(1, page - 2); current <= Math.min(totalPages, page + 2); current += 1) makePageButton(String(current), current, false, current === page);
      makePageButton('›', Math.min(totalPages, page + 1), page === totalPages);
    };

    search.addEventListener('input', () => { page = 1; render(); });
    pageSizeSelect.addEventListener('change', () => { pageSize = Number(pageSizeSelect.value) || 10; page = 1; render(); });
    sortButtons.forEach((button) => {
      button.addEventListener('click', () => {
        const nextIndex = Number(button.dataset.dtSort);
        sortDirection = sortIndex === nextIndex ? sortDirection * -1 : 1;
        sortIndex = nextIndex;
        sortButtons.forEach((item) => item.removeAttribute('aria-sort'));
        button.setAttribute('aria-sort', sortDirection === 1 ? 'ascending' : 'descending');
        render();
      });
    });
    render();
  });
})();
