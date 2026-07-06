import Plugin from 'src/plugin-system/plugin.class';

export default class MichaPriceOnRequestPlugin extends Plugin {
    init() {
        this._button = this.el;
        this._productId = this.el.dataset.productId;
        this._productName = this.el.dataset.productName;
        this._recipient = this.el.dataset.recipient;
        this._csrfToken = this.el.dataset.csrfToken || '';

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
                    width: 100%; max-width: 480px; position: relative;
                    box-shadow: 0 4px 24px rgba(0,0,0,.15); margin: 1rem;">
                    <button class="micha-por-close" style="
                        position: absolute; top: 1rem; right: 1rem;
                        background: none; border: none; font-size: 1.5rem;
                        cursor: pointer; line-height: 1; color: #6c757d;"
                        aria-label="Schließen">&times;</button>
                    <h3 style="margin-bottom: .5rem; font-size: 1.25rem; font-weight: 600;">Preis anfragen</h3>
                    <p style="margin-bottom: 1rem; color: #6c757d;">${this._productName}</p>

                    <label style="display:block; margin-bottom:.25rem; font-size:.875rem; font-weight:500;">Ihr Name *</label>
                    <input id="micha-por-name" type="text" placeholder="Max Mustermann" style="
                        width:100%; padding:.5rem .75rem; border:1px solid #dee2e6;
                        border-radius:4px; font-size:1rem; margin-bottom:1rem; box-sizing:border-box;">

                    <label style="display:block; margin-bottom:.25rem; font-size:.875rem; font-weight:500;">Ihre E-Mail *</label>
                    <input id="micha-por-email" type="email" placeholder="max@beispiel.de" style="
                        width:100%; padding:.5rem .75rem; border:1px solid #dee2e6;
                        border-radius:4px; font-size:1rem; margin-bottom:1rem; box-sizing:border-box;">

                    <label style="display:block; margin-bottom:.25rem; font-size:.875rem; font-weight:500;">Nachricht (optional)</label>
                    <textarea id="micha-por-message" rows="3" placeholder="Ihre Nachricht..." style="
                        width:100%; padding:.5rem .75rem; border:1px solid #dee2e6;
                        border-radius:4px; font-size:1rem; margin-bottom:1rem; box-sizing:border-box;"></textarea>

                    <div style="display:flex; align-items:flex-start; gap:.5rem; margin-bottom:1rem;">
                        <input id="micha-por-privacy" type="checkbox" style="margin-top:.2rem; flex-shrink:0;">
                        <label for="micha-por-privacy" style="font-size:.875rem; margin-bottom:0;">
                            Ich habe die <a href="/privacy" target="_blank">Datenschutzerklärung</a> gelesen und stimme der Verarbeitung meiner Daten zu. *
                        </label>
                    </div>

                    <div style="position:absolute; left:-9999px; top:-9999px; width:1px; height:1px; overflow:hidden;" aria-hidden="true">
                        <input id="micha-por-honeypot" type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <button class="micha-por-submit btn btn-primary" style="width:100%; padding:.75rem; font-size:1rem;">
                        Anfrage senden
                    </button>
                    <div class="micha-por-feedback" style="display:none; margin-top:.75rem; font-size:.875rem; font-weight:500;"></div>
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
        const name     = document.getElementById('micha-por-name').value.trim();
        const email    = document.getElementById('micha-por-email').value.trim();
        const message  = document.getElementById('micha-por-message').value.trim();
        const privacy  = document.getElementById('micha-por-privacy').checked;
        const honeypot = document.getElementById('micha-por-honeypot').value;
        const feedback = modal.querySelector('.micha-por-feedback');

        if (!name || !email) {
            this._showFeedback(feedback, 'Bitte Name und E-Mail ausfüllen.', 'red');
            return;
        }

        if (!privacy) {
            this._showFeedback(feedback, 'Bitte stimmen Sie der Datenschutzerklärung zu.', 'red');
            return;
        }

        const submitBtn = modal.querySelector('.micha-por-submit');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Wird gesendet...';

        fetch('/micha-price-on-request/send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                productId:   this._productId,
                productName: this._productName,
                name,
                email,
                message,
                website:    honeypot,
                _csrf_token: this._csrfToken
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this._showFeedback(feedback, 'Anfrage wurde gesendet! Wir melden uns bei Ihnen.', 'green');
                submitBtn.style.display = 'none';
            } else {
                this._showFeedback(feedback, data.error || 'Fehler beim Senden. Bitte erneut versuchen.', 'red');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Anfrage senden';
            }
        })
        .catch(() => {
            this._showFeedback(feedback, 'Verbindungsfehler. Bitte erneut versuchen.', 'red');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Anfrage senden';
        });
    }

    _showFeedback(el, message, color) {
        el.style.display = 'block';
        el.style.color = color === 'green' ? '#198754' : '#dc3545';
        el.textContent = message;
    }
}