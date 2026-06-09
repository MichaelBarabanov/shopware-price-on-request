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
            <div class="micha-por-overlay">
                <div class="micha-por-dialog">
                    <button class="micha-por-close" aria-label="Schließen">&times;</button>
                    <h3>Preis anfragen</h3>
                    <p style="color:#6c757d; margin-bottom:1rem;">${this._productName}</p>

                    <label>Ihr Name *</label>
                    <input id="micha-por-name" type="text" placeholder="Max Mustermann">

                    <label>Ihre E-Mail *</label>
                    <input id="micha-por-email" type="email" placeholder="max@beispiel.de">

                    <label>Nachricht (optional)</label>
                    <textarea id="micha-por-message" rows="3" placeholder="Ihre Nachricht..."></textarea>

                    <div class="micha-por-privacy">
                        <input id="micha-por-privacy" type="checkbox">
                        <label for="micha-por-privacy" style="margin-bottom:0;">
                            Ich habe die <a href="/privacy" target="_blank">Datenschutzerklärung</a> gelesen und stimme der Verarbeitung meiner Daten zu. *
                        </label>
                    </div>

                    <div style="display:none;" aria-hidden="true">
                        <input id="micha-por-honeypot" type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <button class="micha-por-submit">Anfrage senden</button>
                    <div class="micha-por-feedback" style="display:none;"></div>
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
                website: honeypot
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