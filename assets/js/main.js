/**
 * Master Car - JavaScript Principal
 */

// =====================================================
// FUNÇÕES UTILITÁRIAS
// =====================================================

/**
 * Formata valor monetário
 */
function formatarMoeda(valor) {
    return 'R$ ' + parseFloat(valor).toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+,)/g, '$1.');
}

/**
 * Formata data
 */
function formatarData(data) {
    if (!data) return '-';
    const d = new Date(data);
    return d.toLocaleDateString('pt-BR');
}

/**
 * Formata CPF/CNPJ
 */
function formatarCpfCnpj(valor) {
    valor = valor.replace(/\D/g, '');
    if (valor.length === 11) {
        return valor.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    }
    return valor.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
}

/**
 * Formata telefone
 */
function formatarTelefone(valor) {
    valor = valor.replace(/\D/g, '');
    if (valor.length === 11) {
        return valor.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    }
    return valor.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
}

/**
 * Limpa caracteres não numéricos
 */
function apenasNumeros(valor) {
    return valor.replace(/\D/g, '');
}

/**
 * Mostra alerta
 */
function mostrarAlerta(mensagem, tipo = 'info') {
    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo}`;
    alerta.innerHTML = `
        <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'danger' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${mensagem}</span>
    `;
    
    const container = document.querySelector('.content') || document.body;
    container.insertBefore(alerta, container.firstChild);
    
    setTimeout(() => {
        alerta.remove();
    }, 5000);
}

/**
 * Confirmação de exclusão
 */
function confirmarExclusao(mensagem = 'Tem certeza que deseja excluir este registro?') {
    return confirm(mensagem);
}

/**
 * Copia texto para clipboard
 */
function copiarTexto(texto) {
    navigator.clipboard.writeText(texto).then(() => {
        mostrarAlerta('Texto copiado para a área de transferência!', 'success');
    }).catch(() => {
        mostrarAlerta('Erro ao copiar texto.', 'danger');
    });
}

// =====================================================
// MÁSCARAS DE INPUT
// =====================================================

/**
 * Aplica máscara de CPF/CNPJ
 */
function mascaraCpfCnpj(input) {
    let valor = input.value.replace(/\D/g, '');
    
    if (valor.length <= 11) {
        // CPF
        valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
        valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
        valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    } else {
        // CNPJ
        valor = valor.replace(/(\d{2})(\d)/, '$1.$2');
        valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
        valor = valor.replace(/(\d{3})(\d)/, '$1/$2');
        valor = valor.replace(/(\d{4})(\d{1,2})$/, '$1-$2');
    }
    
    input.value = valor;
}

/**
 * Aplica máscara de telefone
 */
function mascaraTelefone(input) {
    let valor = input.value.replace(/\D/g, '');
    
    if (valor.length > 10) {
        valor = valor.replace(/(\d{2})(\d)/, '($1) $2');
        valor = valor.replace(/(\d{5})(\d)/, '$1-$2');
    } else {
        valor = valor.replace(/(\d{2})(\d)/, '($1) $2');
        valor = valor.replace(/(\d{4})(\d)/, '$1-$2');
    }
    
    input.value = valor;
}

/**
 * Aplica máscara de CEP
 */
function mascaraCep(input) {
    let valor = input.value.replace(/\D/g, '');
    valor = valor.replace(/(\d{5})(\d)/, '$1-$2');
    input.value = valor;
}

/**
 * Aplica máscara de moeda
 */
function mascaraMoeda(input) {
    let valor = input.value.replace(/\D/g, '');
    valor = (parseInt(valor) / 100).toFixed(2);
    valor = valor.replace('.', ',');
    valor = valor.replace(/(\d)(?=(\d{3})+,)/g, '$1.');
    input.value = 'R$ ' + valor;
}

/**
 * Aplica máscara de placa
 */
function mascaraPlaca(input) {
    let valor = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    
    // Formato Mercosul (ABC1D23) ou antigo (ABC-1234)
    if (valor.length > 3 && /^[A-Z]{3}[0-9]/.test(valor)) {
        // Mercosul
        if (valor.length > 4) {
            valor = valor.substring(0, 3) + '-' + valor.substring(3);
        }
    }
    
    input.value = valor;
}

// =====================================================
// BUSCA CEP
// =====================================================

/**
 * Busca endereço pelo CEP
 */
function buscarCep(cep, callback) {
    cep = cep.replace(/\D/g, '');
    
    if (cep.length !== 8) {
        mostrarAlerta('CEP inválido.', 'warning');
        return;
    }
    
    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(data => {
            if (data.erro) {
                mostrarAlerta('CEP não encontrado.', 'warning');
                return;
            }
            callback(data);
        })
        .catch(() => {
            mostrarAlerta('Erro ao buscar CEP.', 'danger');
        });
}

// =====================================================
// MODAIS
// =====================================================

/**
 * Abre modal
 */
function abrirModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Fecha modal
 */
function fecharModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// =====================================================
// TABS
// =====================================================

/**
 * Alterna entre tabs
 */
function mudarTab(tabId) {
    // Remove classe active de todas as tabs
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    // Adiciona classe active na tab selecionada
    document.querySelector(`.tab[data-tab="${tabId}"]`).classList.add('active');
    document.getElementById(tabId).classList.add('active');
}

// =====================================================
// SIDEBAR
// =====================================================

/**
 * Toggle sidebar
 */
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
    document.querySelector('.main-content').classList.toggle('sidebar-collapsed');
}

// =====================================================
// UPLOAD DE ARQUIVOS
// =====================================================

/**
 * Preview de imagem
 */
function previewImagem(input, previewId) {
    const preview = document.getElementById(previewId);
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// =====================================================
// DATATABLES SIMPLES
// =====================================================

/**
 * Inicializa busca em tabela
 */
function initTableSearch(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    if (!input || !table) return;
    
    input.addEventListener('keyup', function() {
        const termo = this.value.toLowerCase();
        const linhas = table.querySelectorAll('tbody tr');
        
        linhas.forEach(linha => {
            const texto = linha.textContent.toLowerCase();
            linha.style.display = texto.includes(termo) ? '' : 'none';
        });
    });
}

// =====================================================
// AJAX HELPERS
// =====================================================

/**
 * Faz requisição AJAX
 */
async function ajax(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const config = { ...defaultOptions, ...options };
    
    try {
        const response = await fetch(url, config);
        
        if (!response.ok) {
            throw new Error('Erro na requisição');
        }
        
        return await response.json();
    } catch (error) {
        console.error('Erro AJAX:', error);
        throw error;
    }
}

// =====================================================
// INICIALIZAÇÃO
// =====================================================

document.addEventListener('DOMContentLoaded', function() {
    // Inicializa máscaras
    document.querySelectorAll('[data-mask="cpf_cnpj"]').forEach(input => {
        input.addEventListener('input', () => mascaraCpfCnpj(input));
    });
    
    document.querySelectorAll('[data-mask="telefone"]').forEach(input => {
        input.addEventListener('input', () => mascaraTelefone(input));
    });
    
    document.querySelectorAll('[data-mask="cep"]').forEach(input => {
        input.addEventListener('input', () => mascaraCep(input));
    });
    
    document.querySelectorAll('[data-mask="moeda"]').forEach(input => {
        input.addEventListener('input', () => mascaraMoeda(input));
    });
    
    document.querySelectorAll('[data-mask="placa"]').forEach(input => {
        input.addEventListener('input', () => mascaraPlaca(input));
    });
    
    // Busca CEP automática
    document.querySelectorAll('[data-cep]').forEach(input => {
        input.addEventListener('blur', function() {
            const cep = this.value;
            const form = this.closest('form');
            
            buscarCep(cep, data => {
                const endereco = form.querySelector('[name="endereco"]');
                const bairro = form.querySelector('[name="bairro"]');
                const cidade = form.querySelector('[name="cidade"]');
                const estado = form.querySelector('[name="estado"]');
                
                if (endereco) endereco.value = data.logradouro;
                if (bairro) bairro.value = data.bairro;
                if (cidade) cidade.value = data.localidade;
                if (estado) estado.value = data.uf;
            });
        });
    });
    
    // Tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            mudarTab(this.dataset.tab);
        });
    });
    
    // Fechar modal ao clicar fora
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
    
    // Toggle sidebar mobile
    const toggleBtn = document.querySelector('.toggle-sidebar');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleSidebar);
    }
    
    // Confirmação de exclusão
    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const mensagem = this.dataset.confirm || 'Tem certeza?';
            if (!confirm(mensagem)) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-hide alerts
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
});
