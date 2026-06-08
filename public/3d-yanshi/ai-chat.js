// AI 报修助手聊天组件
// API: /api/ai/chat (对话)  /api/ai/submit (提交)  /api/ai/quickTypes (快速类型)

const AI_API = '/api/ai/';

(function() {
    'use strict';

    let chatHistory = [];
    let pendingRepair = null;
    let isOpen = false;

    // 获取业主登录token（如果在同域下登录过）
    function getOwnerToken() {
        return localStorage.getItem('owner_token') || '';
    }

    // 构建请求头
    function makeHeaders() {
        const headers = { 'Content-Type': 'application/json' };
        const token = getOwnerToken();
        if (token) headers['Authorization'] = 'Bearer ' + token;
        return headers;
    }

    function createWidget() {
        const html = `
<div id="ai-chat-widget">
    <button id="ai-chat-btn" title="AI智能报修助手">
        🤖
        <span class="pulse-dot"></span>
    </button>
    <div id="ai-chat-panel">
        <div class="chat-header">
            <span>🤖 AI 智能报修助手</span>
            <span class="close-btn" id="chat-close">✕</span>
        </div>
        <div class="chat-messages" id="chat-messages"></div>
        <div class="quick-types" id="quick-types"></div>
        <div class="typing-indicator" id="typing">
            <span></span><span></span><span></span>
        </div>
        <div class="chat-input-area">
            <input type="text" id="chat-input" placeholder="描述您遇到的问题..." />
            <button id="chat-send" title="发送">▶</button>
        </div>
    </div>
</div>`;
        document.body.insertAdjacentHTML('beforeend', html);
        bindEvents();
        loadQuickTypes();
        addMsg('ai', '您好！我是大圣智慧物业的AI报修助手 🤖\n请直接告诉我您遇到的问题，比如：\n• "厨房水龙头漏水"\n• "客厅空调不制冷"\n我会自动帮您生成报修单！');
    }

    function bindEvents() {
        document.getElementById('ai-chat-btn').addEventListener('click', toggleChat);
        document.getElementById('chat-close').addEventListener('click', () => {
            document.getElementById('ai-chat-panel').classList.remove('show');
            isOpen = false;
        });
        document.getElementById('chat-send').addEventListener('click', sendMessage);
        document.getElementById('chat-input').addEventListener('keydown', e => {
            if (e.key === 'Enter') sendMessage();
        });
    }

    function toggleChat() {
        isOpen = !isOpen;
        document.getElementById('ai-chat-panel').classList.toggle('show', isOpen);
        if (isOpen) {
            document.getElementById('chat-input').focus();
            scrollBottom();
        }
    }

    async function sendMessage() {
        const input = document.getElementById('chat-input');
        const msg = input.value.trim();
        if (!msg) return;

        if (msg === '确认' && pendingRepair) {
            input.value = '';
            addMsg('user', '确认提交');
            showTyping();
            await submitRepair();
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
            const resp = await fetch(AI_API + 'chat', {
                method: 'POST',
                headers: makeHeaders(),
                body: JSON.stringify({ message, history: chatHistory })
            });
            const data = await resp.json();
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
            const resp = await fetch(AI_API + 'submit', {
                method: 'POST',
                headers: makeHeaders(),
                body: JSON.stringify({
                    title: pendingRepair.title,
                    content: 'AI智能报修：' + pendingRepair.title,
                    repair_type: pendingRepair.repairType,
                    is_urgent: pendingRepair.isUrgent,
                    location: pendingRepair.location
                })
            });
            const data = await resp.json();
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
            const resp = await fetch(AI_API + 'quickTypes');
            const data = await resp.json();
            if (data.code === 0 && data.data) {
                const container = document.getElementById('quick-types');
                data.data.forEach(t => {
                    const btn = document.createElement('span');
                    btn.className = 'quick-btn';
                    btn.textContent = t.icon + ' ' + t.name;
                    btn.title = t.examples;
                    btn.addEventListener('click', () => {
                        document.getElementById('chat-input').value = t.examples.split('、')[0];
                        sendMessage();
                    });
                    container.appendChild(btn);
                });
            }
        } catch (e) { /* silent */ }
    }

    function addMsg(type, text) {
        const container = document.getElementById('chat-messages');
        const div = document.createElement('div');
        div.className = 'msg-bubble msg-' + type;
        div.textContent = text;
        container.appendChild(div);
        scrollBottom();
    }

    function showTyping() {
        document.getElementById('typing').classList.add('show');
        scrollBottom();
    }

    function hideTyping() {
        document.getElementById('typing').classList.remove('show');
    }

    function scrollBottom() {
        const container = document.getElementById('chat-messages');
        setTimeout(() => { container.scrollTop = container.scrollHeight; }, 50);
    }

    // Init
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createWidget);
    } else {
        createWidget();
    }
})();
