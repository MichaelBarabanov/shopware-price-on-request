import Plugin from 'src/plugin-system/plugin.class';

export default class MichaPriceOnRequestPlugin extends Plugin {
    init() {
        this._button = this.el;
        this._productId = this.el.dataset.productId;
        this._productName = this.el.dataset.productName;
        this._recipient = this.el.dataset.recipient;

        this._button.addEventListener('click', this._openModal.bind(this));
    }

    _openModal() {
        const existing = document.getElementById('micha-por-modal');
        if (existing) existing.remove();

        const modal = document.createElement('div');
        modal.id = 'micha-por-modal';
        modal.innerHTML = `
            <div class="micha-por-overlay" style="
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0,0,0,0.5); z-index: 9999;
                display: flex; align-items: center; justify-content: center;">
                <div class="micha-por-dialog" style="
                    background: white; padding: 2rem; border-radius: 8px;
                    width: 100%; max-width: 480px; position: relative;">
                    <button class="micha-por-close" style="
                        position: absolute; top: 1rem; right: 1rem;
                        background: none; border: none; font-size: 1.5rem; cursor: pointer;">
                        &times;
                    </button>
                    <h3 style="margin-bottom: 1rem;">Preis anfragen</h3>
                    <p style="margin-bottom: 1rem; color: #666;">${this._productName}</p>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: .25rem;">Ihr Name</label>
                        <input id="micha-por-name" type="text" style="width: 100%; padding: .5rem; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: .25rem;">Ihre E-Mail</label>
                        <input id="micha-por-email" type="email" style="width: 100%; padding: .5rem; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: .25rem;">Nachricht (optional)</label>
                        <textarea id="micha-por-message" rows="3" style="width: 100%; padding: .5rem; border: 1px solid #ccc; border-radius: 4px;"></textarea>
                    </div>
                    <div style="display: none;" aria-hidden="true">
                        <label>Website</label>
                        <input id="micha-por-honeypot" type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    <button class="micha-por-submit btn btn-primary" style="width: 100%;">
                        Anfrage senden
                    </button>
                    <div class="micha-por-feedback" style="margin-top: 1rem; display: none;"></div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        modal.querySelector('.micha-por-close').addEventListener('click', () => modal.remove());
        modal.querySelector('.micha-por-overlay').addEventListener('click', (e) => {
            if (e.target === modal.querySelector('.micha-por-overlay')) modal.remove();
        });
        modal.querySelector('.micha-por-submit').addEventListener('click', this._submitForm.bind(this, modal));
    }

    _submitForm(modal) {
        const name = document.getElementById('micha-por-name').value.trim();
        const email = document.getElementById('micha-por-email').value.trim();
        const message = document.getElementById('micha-por-message').value.trim();
        const honeypot = document.getElementById('micha-por-honeypot').value;
        const feedback = modal.querySelector('.micha-por-feedback');

        if (!name || !email) {
            feedback.style.display = 'block';
            feedback.style.color = 'red';
            feedback.textContent = 'Bitte Name und E-Mail ausfüllen.';
            return;
        }

        fetch('/micha-price-on-request/send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                productId: this._productId,
                productName: this._productName,
                name,
                email,
                message,
                website: honeypot
            })
        })
        .then(r => r.json())
        .then(data => {
            feedback.style.display = 'block';
            if (data.success) {
                feedback.style.color = 'green';
                feedback.textContent = 'Anfrage wurde gesendet!';
                modal.querySelector('.micha-por-submit').disabled = true;
            } else {
                feedback.style.color = 'red';
                feedback.textContent = data.error || 'Fehler beim Senden. Bitte versuche es erneut.';
            }
        })
        .catch(() => {
            feedback.style.display = 'block';
            feedback.style.color = 'red';
            feedback.textContent = 'Fehler beim Senden. Bitte versuche es erneut.';
        });
    }
}