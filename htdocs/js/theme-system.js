
class ThemeManager {
    constructor() {
        this.selectedTheme = localStorage.getItem('logfinder_theme') || 'light';
        this.tempSelectedTheme = this.selectedTheme;
        this.init();
    }

    init() {
        this.applyStoredTheme();
        this.createThemeModal();
        this.createThemeToggleButton();
        this.bindEvents();
        
        if (!localStorage.getItem('logfinder_theme_selected')) {
            setTimeout(() => {
                this.openThemeModal();
            }, 1000);
        }
    }

    applyStoredTheme() {
        document.documentElement.setAttribute('data-theme', this.selectedTheme);
    }

    createThemeModal() {
        const modalHTML = `
            <div id="themeModal" class="theme-modal">
                <div class="theme-modal-content">
                    <div class="theme-modal-header">
                        <h3 class="theme-modal-title">
                            <i class="fas fa-palette"></i>
                            Tema Seçin
                        </h3>
                        <p class="theme-modal-subtitle">Gözlerinize en uygun temayı seçin</p>
                    </div>
                    
                    <div class="theme-options">
                        <div class="theme-option" data-theme="light" onclick="themeManager.selectTheme('light')">
                            <i class="fas fa-sun" style="color: #f6d55c;"></i>
                            <div class="theme-option-title">Gündüz Tema</div>
                            <div class="theme-option-desc">Aydınlık ve temiz görünüm</div>
                        </div>
                        
                        <div class="theme-option" data-theme="neon" onclick="themeManager.selectTheme('neon')">
                            <i class="fas fa-bolt" style="color: #a855f7; filter: drop-shadow(0 0 8px rgba(168,85,247,0.8));"></i>
                            <div class="theme-option-title">Neon Tema</div>
                            <div class="theme-option-desc">Mor ışıklı cyberpunk stili</div>
                        </div>
                        
                        <div class="theme-option" data-theme="corporate" onclick="themeManager.selectTheme('corporate')">
                            <i class="fas fa-building" style="color: #a855f7; filter: drop-shadow(0 0 8px rgba(168,85,247,0.8));"></i>
                            <div class="theme-option-title">Gündüz Mor Tema</div>
                            <div class="theme-option-desc">Beyaz + neon mor akcentler</div>
                        </div>
                    </div>
                    
                    <div class="theme-modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="themeManager.closeThemeModal()">
                            <i class="fas fa-times"></i>
                            İptal
                        </button>
                        <button type="button" class="btn btn-primary" onclick="themeManager.applyTheme()">
                            <i class="fas fa-check"></i>
                            Uygula
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    createThemeToggleButton() {
        const buttonHTML = `
            <button class="theme-toggle-btn" onclick="themeManager.openThemeModal()" title="Tema Değiştir">
                <i class="fas fa-palette"></i>
            </button>
        `;
        document.body.insertAdjacentHTML('beforeend', buttonHTML);
    }

    bindEvents() {
        document.addEventListener('click', (e) => {
            if (e.target && e.target.id === 'themeModal') {
                this.closeThemeModal();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeThemeModal();
            }
            if (e.ctrlKey && e.key === 't') {
                e.preventDefault();
                this.openThemeModal();
            }
        });
    }

    openThemeModal() {
        const modal = document.getElementById('themeModal');
        if (modal) {
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
            this.tempSelectedTheme = this.selectedTheme;
            this.updateThemeSelection();
        }
    }

    closeThemeModal() {
        const modal = document.getElementById('themeModal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
            this.tempSelectedTheme = this.selectedTheme;
            this.updateThemeSelection();
            document.documentElement.setAttribute('data-theme', this.selectedTheme);
        }
    }

    selectTheme(theme) {
        this.tempSelectedTheme = theme;
        this.updateThemeSelection();
        document.documentElement.setAttribute('data-theme', theme);
    }

    applyTheme() {
        this.selectedTheme = this.tempSelectedTheme;
        localStorage.setItem('logfinder_theme', this.selectedTheme);
        localStorage.setItem('logfinder_theme_selected', 'true');
        document.documentElement.setAttribute('data-theme', this.selectedTheme);
        
        this.showSuccessMessage();
        this.closeThemeModal();
    }

    updateThemeSelection() {
        document.querySelectorAll('.theme-option').forEach(option => {
            option.classList.remove('active');
            if (option.getAttribute('data-theme') === this.tempSelectedTheme) {
                option.classList.add('active');
            }
        });
    }

    showSuccessMessage() {
        const successDiv = document.createElement('div');
        successDiv.className = 'alert alert-success theme-success-alert';
        successDiv.innerHTML = `
            <i class="fas fa-check-circle"></i>
            Tema başarıyla değiştirildi! Sayfa yenilendikten sonra da aynı kalacak.
        `;
        successDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10001;
            min-width: 300px;
            animation: slideInRight 0.5s ease;
            padding: 1rem;
            border-radius: 0.5rem;
            background: var(--success-color);
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        `;
        
        document.body.appendChild(successDiv);
        
        setTimeout(() => {
            successDiv.style.animation = 'slideOutRight 0.5s ease';
            setTimeout(() => {
                if (document.body.contains(successDiv)) {
                    document.body.removeChild(successDiv);
                }
            }, 500);
        }, 3000);
    }

    addRequiredCSS() {
        if (!document.getElementById('theme-system-css')) {
            const style = document.createElement('style');
            style.id = 'theme-system-css';
            style.textContent = `
                /* Theme Modal Styles */
                .theme-modal {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.6);
                    z-index: 10000;
                    justify-content: center;
                    align-items: center;
                }
                
                .theme-modal-content {
                    background: var(--white);
                    border-radius: 1rem;
                    padding: 2rem;
                    width: 90%;
                    max-width: 400px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    transform: scale(0.9);
                    transition: transform 0.3s ease;
                }
                
                .theme-modal.show .theme-modal-content {
                    transform: scale(1);
                }
                
                .theme-modal-header {
                    text-align: center;
                    margin-bottom: 2rem;
                }
                
                .theme-modal-title {
                    font-size: 1.5rem;
                    font-weight: 700;
                    color: var(--gray-900);
                    margin-bottom: 0.5rem;
                }
                
                .theme-modal-subtitle {
                    color: var(--gray-600);
                }
                
                .theme-options {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                    gap: 1rem;
                    margin-bottom: 2rem;
                }
                
                .theme-option {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    padding: 1.5rem 1rem;
                    border: 2px solid var(--gray-200);
                    border-radius: 0.75rem;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    text-align: center;
                }
                
                .theme-option:hover {
                    border-color: var(--accent-color);
                    transform: translateY(-2px);
                    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
                }
                
                .theme-option.active {
                    border-color: var(--accent-color);
                    background: linear-gradient(135deg, var(--accent-color), var(--success-color));
                    color: white;
                }
                
                [data-theme="neon"] .theme-option.active {
                    background: linear-gradient(135deg, #a855f7, #c084fc);
                    border-color: #a855f7;
                    box-shadow: 0 0 20px rgba(168, 85, 247, 0.5);
                }
                
                [data-theme="corporate"] .theme-option.active {
                    background: linear-gradient(135deg, #a855f7, #c084fc);
                    border: 2px solid #a855f7;
                    color: white;
                    box-shadow: 0 0 20px rgba(168, 85, 247, 0.4);
                }
                
                .theme-option i {
                    font-size: 2.5rem;
                    margin-bottom: 0.75rem;
                }
                
                .theme-option-title {
                    font-weight: 600;
                    margin-bottom: 0.25rem;
                }
                
                .theme-option-desc {
                    font-size: 0.85rem;
                    opacity: 0.8;
                }
                
                .theme-modal-footer {
                    display: flex;
                    gap: 1rem;
                    justify-content: flex-end;
                }
                
                .theme-toggle-btn {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    background: var(--accent-color);
                    color: white;
                    border: none;
                    width: 60px;
                    height: 60px;
                    border-radius: 50%;
                    font-size: 1.5rem;
                    cursor: pointer;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
                    transition: all 0.3s ease;
                    z-index: 9999;
                }
                
                .theme-toggle-btn:hover {
                    transform: scale(1.1);
                    box-shadow: 0 6px 30px rgba(0,0,0,0.3);
                }
                
                @keyframes slideInRight {
                    from { transform: translateX(100%); }
                    to { transform: translateX(0); }
                }
                
                @keyframes slideOutRight {
                    from { transform: translateX(0); }
                    to { transform: translateX(100%); }
                }
                
                @media (max-width: 768px) {
                    .theme-options {
                        grid-template-columns: 1fr;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }
}

let themeManager;
document.addEventListener('DOMContentLoaded', () => {
    themeManager = new ThemeManager();
    themeManager.addRequiredCSS();
});
