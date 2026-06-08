// AI 报修助手聊天组件
// API: /api/ai/chat (对话)  /api/ai/submit (提交)  /api/ai/quickTypes (快速类型)

const AI_API = '/api/ai/';

(function() {
    'use strict';

    let chatHistory = [];
    let pendingRepair = null;
    let isOpen = false;

    function getOwnerToken() { return localStorage.getItem('owner_token') || ''; }

    function makeHeaders() {
        const headers = { 'Content-Type': 'application/json' };
        var token = getOwnerToken();
        if (token) headers['Authorization'] = 'Bearer ' + token;
        return headers;
    }

    // 弹出确认提交框（含手机号输入）
    function showConfirmDialog() {
        if (!pendingRepair) return;
        var hasToken = !!getOwnerToken();
        var urgentTag = pendingRepair.isUrgent ? '【紧急】' : '';
        var locTag = pendingRepair.location ? '(' + pendingRepair.location + ')' : '';

        document.body.insertAdjacentHTML('beforeend',
            '<div id="ai-confirm-overlay">' +
                '<div id="ai-confirm-box">' +
                    '<h3>确认报修信息</h3>' +
                    '<div class="confirm-info">' +
                        '<div class="cf-row"><span class="cf-label">类型：</span><span class="cf-value">' + urgentTag + pendingRepair.repairType + '维修</span></div>' +
                        '<div class="cf-row"><span class="cf-label">位置：</span><span class="cf-value">' + (pendingRepair.location || '待确认') + '</span></div>' +
                        '<div class="cf-row"><span class="cf-label">描述：</span><span class="cf-value">' + pendingRepair.title + '</span></div>' +
                    '</div>' +
                    '<div class="cf-phone-row"><label>联系电话：</label><input type="tel" id="ai-phone-input" placeholder="' + (hasToken ? '已登录可不填' : '请输入11位手机号') + '" maxlength="13" />' + (hasToken ? '<small class="cf-hint">已登录可不填</small>' : '') + '</div>' +
                    '<div class="cf-buttons"><button id="ai-confirm-cancel" class="cf-btn">取消</button><button id="ai-confirm-ok" class="cf-btn primary">确认提交</button></div>' +
                '</div></div>'
        );

        var overlay = document.getElementById('ai-confirm-overlay');
        var phoneInput = document.getElementById('ai-phone-input');

        document.getElementById('ai-confirm-cancel').addEventListener('click', function() {
            overlay.remove();
            addMsg('ai', '已取消。如需修改请重新描述问题。');
            pendingRepair = null;
        });

        document.getElementById('ai-confirm-ok').addEventListener('click', async function() {
            var phone = phoneInput.value.replace(/\s/g, '');
            if (!hasToken && !phone) { phoneInput.style.borderColor = '#e74c3c'; phoneInput.focus(); return; }
            if (phone && !/^1[3-9]\d{9}$/.test(phone)) { phoneInput.style.borderColor = '#e74c3c'; phoneInput.focus(); return; }
            pendingRepair.phone = phone;
            overlay.remove();
            addMsg('user', '确认提交');
            showTyping();
            await submitRepair();
        });

        phoneInput.addEventListener('keydown', function(e) { if (e.key === 'Enter') document.getElementById('ai-confirm-ok').click(); });
        setTimeout(function() { phoneInput.focus(); }, 100);
    }

    function createWidget() {
        var html =
'<div id="ai-chat-widget">'+
    '<button id="ai-chat-btn" title="AI智能报修助手">\n        🤖\n        <span class="pulse-dot"></span>\n    </button>\n    <div id="ai-chat-panel">\n        <div class="chat-header">\n            <span>🤖 AI 智能报修助手</span>\n            <span class="close-btn" id="chat-close">✕</span>\n        </div>\n        <div class="chat-messages" id="chat-messages"></div>\n        <div class="quick-types" id="quick-types"></div>\n        <div class="typing-indicator" id="typing"><span></span><span></span><span></span></div>\n        <div class="chat-input-area">\n            <input type="text" id="chat-input" placeholder="描述您遇到的问题..." />\n            <button id="chat-send" title="发送">▶</button>\n        </div>\n    </div>\n</div>';
        document.body.insertAdjacentHTML('beforeend', html);
        bindEvents();
        loadQuickTypes();
        addMsg('ai', '您好！我是大圣智慧物业的AI报修助手\n请直接告诉我您遇到的问题，比如：\n- "厨房水龙头漏水"\n- "客厅空调不制冷"\n我会自动帮您生成报修单！');
    }

    function bindEvents() {
        document.getElementById('ai-chat-btn').addEventListener('click', toggleChat);
        document.getElementById('chat-close').addEventListener('click', function() {
            document.getElementById('ai-chat-panel').classList.remove('show');
            isOpen = false;
        });
        document.getElementById('chat-send').addEventListener('click', sendMessage);
        document.getElementById('chat-input').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') sendMessage();
        });
    }

    function toggleChat() {
        isOpen = !isOpen;
        document.getElementById('ai-chat-panel').classList.toggle('show', isOpen);
        if (isOpen) { document.getElementById('chat-input').focus(); scrollBottom(); }
    }

    async function sendMessage() {
        var input = document.getElementById('chat-input');
        var msg = input.value.trim();
        if (!msg) return;

        if (msg === '确认' && pendingRepair) {
            input.value = '';
            showConfirmDialog();
            return;
        }

        input.value = '';
        addMsg('user', msg);
        chatHistory.push({ role: 'user', content: msg });
        showTyping();
        await chatWithAI(msg);
    }

    async function chatWithAI(message) {
        try {
            var resp = await fetch(AI_API + 'chat', {
                method: 'POST',
                headers: makeHeaders(),
                body: JSON.stringify({ message: message, history: chatHistory })
            });
            var data = await resp.json();
            hideTyping();

            if (data.code === 0 && data.data) {
                addMsg('ai', data.data.reply);
                chatHistory.push({ role: 'assistant', content: data.data.reply });

                if (data.data.action === 'confirm') {
                    pendingRepair = {
                        repairType: data.data.repairType,
                        isUrgent: data.data.isUrgent,
                        location: data.data.location,
                        title: message
                    };
                }
            } else {
                addMsg('ai', '抱歉，系统暂时无法处理，请稍后重试。');
            }
        } catch (e) {
            hideTyping();
            addMsg('ai', '网络异常，请检查连接后重试。');
        }
    }

    async function submitRepair() {
        if (!pendingRepair) return;
        try {
            var resp = await fetch(AI_API + 'submit', {
                method: 'POST',
                headers: makeHeaders(),
                body: JSON.stringify({
                    title: pendingRepair.title,
                    content: 'AI智能报修：' + pendingRepair.title,
                    repair_type: pendingRepair.repairType,
                    is_urgent: pendingRepair.isUrgent,
                    location: pendingRepair.location,
                    phone: pendingRepair.phone || ''
                })
            });
            var data = await resp.json();
            hideTyping();

            if (data.code === 0 && data.data) {
                addMsg('ai', data.data.reply);
                pendingRepair = null;
            } else {
                addMsg('ai', '提交失败：' + (data.msg || '未知错误'));
            }
        } catch (e) {
            hideTyping();
            addMsg('ai', '提交失败，请稍后重试。');
        }
    }

    async function loadQuickTypes() {
        try {
            var resp = await fetch(AI_API + 'quickTypes');
            var data = await resp.json();
            if (data.code === 0 && data.data) {
                var container = document.getElementById('quick-types');
                data.data.forEach(function(t) {
                    var btn = document.createElement('span');
                    btn.className = 'quick-btn';
                    btn.textContent = t.icon + ' ' + t.name;
                    btn.title = t.examples;
                    btn.addEventListener('click', function() {
                        document.getElementById('chat-input').value = t.examples.split('、')[0];
                        sendMessage();
                    });
                    container.appendChild(btn);
                });
            }
        } catch (e) {}
    }

    function addMsg(type, text) {
        var container = document.getElementById('chat-messages');
        var div = document.createElement('div');
        div.className = 'msg-bubble msg-' + type;
        div.textContent = text;
        container.appendChild(div);
        scrollBottom();
    }

    function showTyping() { document.getElementById('typing').classList.add('show'); scrollBottom(); }
    function hideTyping() { document.getElementById('typing').classList.remove('show'); }
    function scrollBottom() {
        var c = document.getElementById('chat-messages');
        setTimeout(function() { c.scrollTop = c.scrollHeight; }, 50);
    }

    // Init
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', createWidget);
    else createWidget();

})();
