(() => {
  const loginView = document.querySelector('#login-view');
  const dashboardView = document.querySelector('#dashboard-view');
  const loginForm = document.querySelector('#login-form');
  const loginStatus = document.querySelector('#login-status');
  const dashboardStatus = document.querySelector('#dashboard-status');
  const ngoNameEl = document.querySelector('#ngo-name');
  const logoutButton = document.querySelector('#logout-button');
  const donationList = document.querySelector('#donation-list');
  const cardTemplate = document.querySelector('#donation-card-template');

  function showLogin() {
    loginView.hidden = false;
    dashboardView.hidden = true;
  }

  function showDashboard(ngo) {
    loginView.hidden = true;
    dashboardView.hidden = false;
    ngoNameEl.textContent = ngo.name;
  }

  function formatDeadline(value) {
    const date = new Date(value.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
  }

  function renderDonations(donations) {
    donationList.innerHTML = '';
    if (donations.length === 0) {
      donationList.innerHTML = '<p class="empty-state">No donations available right now. Check back soon.</p>';
      return;
    }

    donations.forEach(donation => {
      const card = cardTemplate.content.cloneNode(true);
      card.querySelector('.donation-food-type').textContent = donation.food_type;
      card.querySelector('.donation-status').textContent = donation.status;
      card.querySelector('.donation-servings').textContent = `${donation.servings} servings`;
      card.querySelector('.donation-location').textContent = `Pickup at ${donation.pickup_location}`;
      card.querySelector('.donation-deadline').textContent = `Pickup by ${formatDeadline(donation.pickup_deadline)}`;
      card.querySelector('.donation-notes').textContent = donation.food_notes || '';

      const acceptButton = card.querySelector('.accept-button');
      acceptButton.addEventListener('click', () => acceptDonation(donation.id, acceptButton));

      donationList.appendChild(card);
    });
  }

  async function loadDashboard() {
    dashboardStatus.textContent = '';
    dashboardStatus.className = 'form-status';
    try {
      const response = await fetch('api/ngo_dashboard.php', { headers: { Accept: 'application/json' } });
      const payload = await response.json();
      if (!response.ok || !payload.success) throw new Error(payload.message || 'Could not load donations.');
      renderDonations(payload.donations);
    } catch (error) {
      dashboardStatus.textContent = error.message;
      dashboardStatus.classList.add('error');
    }
  }

  async function acceptDonation(donationId, button) {
    button.disabled = true;
    button.textContent = 'Accepting...';
    try {
      const response = await fetch('api/ngo_dashboard.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ donation_id: donationId }),
      });
      const payload = await response.json();
      if (!response.ok || !payload.success) throw new Error(payload.message || 'Could not accept this donation.');
      dashboardStatus.textContent = 'Donation accepted. Coordinate pickup with the donor.';
      dashboardStatus.className = 'form-status success';
      loadDashboard();
    } catch (error) {
      dashboardStatus.textContent = error.message;
      dashboardStatus.className = 'form-status error';
      button.disabled = false;
      button.textContent = 'Accept donation';
    }
  }

  async function checkSession() {
    try {
      const response = await fetch('api/ngo_auth.php', { headers: { Accept: 'application/json' } });
      const payload = await response.json();
      if (payload.success && payload.ngo) {
        showDashboard(payload.ngo);
        loadDashboard();
      } else {
        showLogin();
      }
    } catch {
      showLogin();
    }
  }

  loginForm.addEventListener('submit', async event => {
    event.preventDefault();
    loginStatus.textContent = '';
    loginStatus.className = 'form-status';
    const formData = new FormData(loginForm);

    try {
      const response = await fetch('api/ngo_auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({
          action: 'login',
          phone: formData.get('phone'),
          password: formData.get('password'),
        }),
      });
      const payload = await response.json();
      if (!response.ok || !payload.success) throw new Error(payload.message || 'Login failed.');
      showDashboard(payload.ngo);
      loadDashboard();
    } catch (error) {
      loginStatus.textContent = error.message;
      loginStatus.classList.add('error');
    }
  });

  logoutButton.addEventListener('click', async () => {
    try {
      await fetch('api/ngo_auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ action: 'logout' }),
      });
    } finally {
      showLogin();
      loginForm.reset();
    }
  });

  checkSession();
})();
