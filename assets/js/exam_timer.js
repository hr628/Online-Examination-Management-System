/**
 * exam_timer.js
 * Countdown timer for the exam-taking page.
 *
 * Usage:
 *   const timer = new ExamTimer(totalSeconds, elementId, onExpireCallback);
 *   timer.start();
 */

class ExamTimer {
    /**
     * @param {number}   totalSeconds     - Remaining seconds at page load
     * @param {string}   elementId        - ID of the display element
     * @param {Function} onExpireCallback - Called when timer reaches 0
     */
    constructor(totalSeconds, elementId, onExpireCallback) {
        this.remaining  = Math.max(0, Math.floor(totalSeconds));
        this.elementId  = elementId;
        this.onExpire   = typeof onExpireCallback === 'function' ? onExpireCallback : () => {};
        this._interval  = null;
        this._el        = null;
    }

    /** Start the countdown. */
    start() {
        this._el = document.getElementById(this.elementId);
        if (!this._el) {
            console.warn('ExamTimer: element #' + this.elementId + ' not found.');
            return;
        }

        this._render();

        this._interval = setInterval(() => {
            this.remaining--;

            if (this.remaining <= 0) {
                this.remaining = 0;
                this._render();
                this._stop();
                this.onExpire();
            } else {
                this._render();
            }
        }, 1000);
    }

    /** Stop the countdown. */
    _stop() {
        if (this._interval) {
            clearInterval(this._interval);
            this._interval = null;
        }
    }

    /** Format remaining seconds and update the DOM element. */
    _render() {
        if (!this._el) return;

        const h   = Math.floor(this.remaining / 3600);
        const m   = Math.floor((this.remaining % 3600) / 60);
        const s   = this.remaining % 60;

        const parts = [];
        if (h > 0)  parts.push(this._pad(h) + 'h');
        parts.push(this._pad(m) + 'm');
        parts.push(this._pad(s) + 's');

        this._el.innerHTML = '<i class="bi bi-clock me-1"></i>' + parts.join(' ');

        // Colour transitions
        this._el.classList.remove('timer-warning', 'timer-danger');

        if (this.remaining <= 0) {
            this._el.classList.add('timer-danger');
            this._el.innerHTML = '<i class="bi bi-alarm me-1"></i>Time\'s up!';
        } else if (this.remaining <= 60) {
            this._el.classList.add('timer-danger');
        } else if (this.remaining <= 300) {     // 5 minutes
            this._el.classList.add('timer-warning');
        }
    }

    /** Zero-pad a number to 2 digits. */
    _pad(n) {
        return String(n).padStart(2, '0');
    }
}
