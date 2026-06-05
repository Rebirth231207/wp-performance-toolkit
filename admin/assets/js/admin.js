/**
 * WordPress Performance Toolkit - Admin Dashboard
 * Pure Vanilla JS (ES6)
 */
document.addEventListener('DOMContentLoaded', () => {
    
    const WPPT_Dashboard = {
        
        // Data passed from PHP via wp_localize_script
        stats: window.wpptStats || null,

        /**
         * Initialize the Dashboard components
         */
        init() {
            if (!this.stats) return;

            this.updateMetricCards();
            this.animateScore(this.stats.score);
            this.renderHistoryChart();
            this.initEventListeners();
            
            console.log('WPPT: Dashboard UI Rendered.');
        },

        /**
         * Update the HTML text values for the metric cards
         */
        updateMetricCards() {
            const s = this.stats;
            
            const elements = {
                'metric-requests': s.requests,
                'metric-load-time': s.load_time + 'ms',
                'metric-ttfb': s.ttfb + 'ms',
                'metric-dom': s.dom_count
            };

            for (const [id, value] of Object.entries(elements)) {
                const el = document.getElementById(id);
                if (el) el.textContent = value || '--';
            }

            // Display message if no data is found
            if (s.load_time === 0) {
                const msg = document.getElementById('wppt-last-run-msg');
                if (msg) msg.textContent = s.i18n.no_data;
            }
        },

        /**
         * Animate the circular score gauge using SVG.
         */
        animateScore(score) {
            const container = document.querySelector('.wppt-score-circle');
            if (!container) return;

            const radius = 54;
            const circumference = 2 * Math.PI * radius;
            
            // Determine color based on score
            let color = 'var(--wppt-success)';
            if (score < 80) color = 'var(--wppt-warning)';
            if (score < 50) color = 'var(--wppt-critical)';

            container.innerHTML = `
                <svg width="120" height="120" viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r="${radius}" fill="none" stroke="#f0f0f1" stroke-width="8" />
                    <circle id="wppt-score-fill" cx="60" cy="60" r="${radius}" fill="none" stroke="${color}" 
                        stroke-width="8" stroke-dasharray="${circumference}" stroke-dashoffset="${circumference}" 
                        stroke-linecap="round" transform="rotate(-90 60 60)" style="transition: stroke-dashoffset 1.5s ease-out;" />
                </svg>
                <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); text-align:center;">
                    <span class="wppt-score-value" id="score-text" style="color:${color}">0</span>
                </div>
            `;

            const fill = document.getElementById('wppt-score-fill');
            const text = document.getElementById('score-text');
            
            // Trigger the dash-offset animation
            setTimeout(() => {
                const offset = circumference - (score / 100) * circumference;
                fill.style.strokeDashoffset = offset;
            }, 100);

            // Animate the text number
            let start = 0;
            const duration = 1500;
            const startTime = performance.now();

            const animateText = (now) => {
                const elapsed = now - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const current = Math.floor(progress * score);
                
                text.textContent = current;

                if (progress < 1) {
                    requestAnimationFrame(animateText);
                }
            };

            requestAnimationFrame(animateText);
        },

        /**
         * Render a custom pure SVG History Chart (Sparkline).
         */
        renderHistoryChart() {
            const container = document.getElementById('wppt-history-chart');
            if (!container || !this.stats.history || this.stats.history.length < 2) {
                container.innerHTML = `<p style="padding:40px; color:var(--wppt-text-muted);">${this.stats.i18n.no_data}</p>`;
                return;
            }

            // Extract values from history: [{t: time, v: value}]
            const data = this.stats.history.map(item => item.v);
            const width = container.clientWidth;
            const height = 250;
            const padding = 40;
            
            const maxVal = Math.max(...data) * 1.2; // Add 20% headroom
            const step = (width - (padding * 2)) / (data.length - 1);

            const points = data.map((val, i) => {
                const x = padding + (i * step);
                const y = height - padding - ((val / maxVal) * (height - padding * 2));
                return `${x},${y}`;
            }).join(' ');

            container.innerHTML = `
                <svg width="100%" height="${height}" preserveAspectRatio="none" style="overflow:visible">
                    <defs>
                        <linearGradient id="chartGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:var(--wppt-accent);stop-opacity:0.2" />
                            <stop offset="100%" style="stop-color:var(--wppt-accent);stop-opacity:0" />
                        </linearGradient>
                    </defs>
                    <!-- Fill Area -->
                    <path d="M ${padding},${height - padding} L ${points} L ${width - padding},${height - padding} Z" fill="url(#chartGrad)" />
                    <!-- Line -->
                    <polyline fill="none" stroke="var(--wppt-accent)" stroke-width="3" stroke-linejoin="round" points="${points}" />
                    <!-- Data Points -->
                    ${data.map((val, i) => {
                        const x = padding + (i * step);
                        const y = height - padding - ((val / maxVal) * (height - padding * 2));
                        return `<circle cx="${x}" cy="${y}" r="5" fill="white" stroke="var(--wppt-accent)" stroke-width="2" />`;
                    }).join('')}
                </svg>
            `;
        },

        /**
         * Initialize Event Listeners (e.g., AJAX triggers)
         */
        initEventListeners() {
            const dbBtn = document.getElementById('wppt-optimize-db');
            if (dbBtn) {
                dbBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.handleDatabaseOptimization(dbBtn);
                });
            }
        },

        /**
         * Logic for the Database Optimizer Button
         */
        handleDatabaseOptimization(btn) {
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Optimizing...';

            const formData = new URLSearchParams();
            formData.append('action', 'wppt_optimize_db');
            formData.append('nonce', this.stats.nonce);

            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.data.message);
                } else {
                    alert('Error: ' + data.data);
                }
            })
            .catch(err => console.error(err))
            .finally(() => {
                btn.disabled = false;
                btn.textContent = originalText;
            });
        }
    };

    WPPT_Dashboard.init();
});