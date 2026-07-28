(() => {
  const dialog = document.querySelector('#donation-dialog');
  const donationForm = document.querySelector('#donation-form');
  const statusMessage = document.querySelector('#form-status');
  const submitButton = donationForm.querySelector('button[type="submit"]');
  const deadlineInput = donationForm.querySelector('[name="pickup_deadline"]');
  const menuButton = document.querySelector('[data-menu-button]');
  const menu = document.querySelector('[data-menu]');

  const fallbackNgos = [
    { name: 'Seva Hands Foundation', description: 'Community meal distribution and last-mile support across Kolkata.', city: 'Salt Lake' },
    { name: 'Annadaata Hub', description: 'Rescuing surplus food from functions and hospitality partners.', city: 'Kolkata' },
    { name: 'Neighbourhood Hope', description: 'Connecting hot meals with underserved communities every day.', city: 'New Town' }
  ];

  function setMinimumDeadline() {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset() + 30);
    deadlineInput.min = now.toISOString().slice(0, 16);
  }

  function initials(name) {
    return name.split(' ').slice(0, 2).map(word => word[0]).join('').toUpperCase();
  }

  function renderNgos(ngos) {
    const list = document.querySelector('#ngo-list');
    const template = document.querySelector('#ngo-card-template');
    list.innerHTML = '';

    ngos.slice(0, 3).forEach(ngo => {
      const card = template.content.cloneNode(true);
      card.querySelector('.ngo-initials').textContent = initials(ngo.name);
      card.querySelector('h3').textContent = ngo.name;
      card.querySelector('.ngo-description').textContent = ngo.description;
      card.querySelector('.ngo-city').textContent = ngo.city;
      list.appendChild(card);
    });
  }

  async function loadNgos() {
    try {
      const response = await fetch('api/ngos.php', { headers: { Accept: 'application/json' } });
      if (!response.ok) throw new Error('Could not load NGO partners.');
      const payload = await response.json();
      renderNgos(payload.ngos?.length ? payload.ngos : fallbackNgos);
    } catch {
      renderNgos(fallbackNgos);
    }
  }

  function openDonationDialog() {
    statusMessage.textContent = '';
    statusMessage.className = 'form-status';
    dialog.showModal();
    donationForm.querySelector('[name="donor_name"]').focus();
  }

  function closeDonationDialog() {
    dialog.close();
  }

  document.querySelectorAll('[data-open-donation]').forEach(button => {
    button.addEventListener('click', openDonationDialog);
  });
  document.querySelector('[data-close-donation]').addEventListener('click', closeDonationDialog);
  dialog.addEventListener('click', event => {
    if (event.target === dialog) closeDonationDialog();
  });

  menuButton.addEventListener('click', () => {
    const isOpen = menu.classList.toggle('open');
    menuButton.setAttribute('aria-expanded', String(isOpen));
  });
  menu.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
    menu.classList.remove('open');
    menuButton.setAttribute('aria-expanded', 'false');
  }));

  donationForm.addEventListener('submit', async event => {
    event.preventDefault();
    statusMessage.textContent = '';
    statusMessage.className = 'form-status';
    submitButton.disabled = true;
    submitButton.textContent = 'Finding a nearby NGO...';

    try {
      const response = await fetch('api/donations.php', {
        method: 'POST',
        body: new FormData(donationForm),
        headers: { Accept: 'application/json' }
      });
      const payload = await response.json();
      if (!response.ok || !payload.success) throw new Error(payload.message || 'We could not save the donation. Please try again.');

      const match = payload.matched_ngo ? ` ${payload.matched_ngo.name} has been suggested as your nearby verified NGO.` : '';
      statusMessage.textContent = `Donation #${payload.donation_id} was created successfully.${match}`;
      statusMessage.classList.add('success');
      donationForm.reset();
      setMinimumDeadline();
    } catch (error) {
      statusMessage.textContent = error.message || 'Something went wrong. Please try again.';
      statusMessage.classList.add('error');
    } finally {
      submitButton.disabled = false;
      submitButton.textContent = 'Find a nearby NGO →';
    }
  });

  setMinimumDeadline();
  loadNgos();
})();
