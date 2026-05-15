<template>
  <div class="ai-assistant">
    <!-- Floating Button -->
    <button class="ai-fab" @click="toggleChat" :class="{ active: isOpen }">
      <div class="ai-fab-content">
        <span class="ai-fab-icon" v-if="!isOpen">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        </span>
        <span class="ai-fab-icon" v-else>
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </span>
      </div>
      <div class="ai-fab-ring" v-if="!isOpen"></div>
    </button>

    <!-- Chat Panel -->
    <Teleport to="body">
      <transition name="chat-panel-fade">
        <div v-if="isOpen" class="ai-chat-panel">
          <!-- Header -->
          <div class="ai-chat-header">
            <div class="ai-header-main">
              <div class="ai-avatar-wrapper">
                <div class="ai-avatar-inner">
                  <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 8h1a4 4 0 1 1 0 8h-1"></path><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"></path><line x1="6" y1="2" x2="6" y2="4"></line><line x1="10" y1="2" x2="10" y2="4"></line><line x1="14" y1="2" x2="14" y2="4"></line></svg>
                </div>
                <div class="ai-online-dot"></div>
              </div>
              <div class="ai-title-group">
                <h4>Cafe AI Assistant</h4>
                <div class="ai-status-row">
                  <span class="ai-pulse-dot"></span>
                  <span class="ai-status-text">Đang trực tuyến</span>
                </div>
              </div>
            </div>
            <div class="ai-header-actions">
              <button class="ai-action-icon" @click="clearChat" title="Làm mới hội thoại">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
              </button>
              <button class="ai-action-icon" @click="toggleChat">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
              </button>
            </div>
          </div>

          <!-- Messages -->
          <div class="ai-chat-body" ref="messagesContainer" @click="handleChatClick">
            <!-- Empty State / Welcome -->
            <div v-if="messages.length === 0" class="ai-welcome-container">
              <div class="ai-welcome-hero">
                <div class="ai-hero-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#D4A373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"></path><path d="M5 3v4"></path><path d="M19 17v4"></path><path d="M3 5h4"></path><path d="M17 19h4"></path></svg>
                </div>
                <h2>Chào mừng trở lại!</h2>
                <p>Tôi có thể giúp gì cho việc quản lý quán hôm nay?</p>
              </div>
              <div class="ai-suggestion-grid">
                <button v-for="s in suggestions" :key="s.text" class="ai-suggestion-card" @click="sendSuggestion(s.text)">
                  <div class="ai-card-content">
                    <span class="ai-card-icon" v-html="s.icon"></span>
                    <span class="ai-card-text">{{ s.text }}</span>
                  </div>
                  <span class="ai-card-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                  </span>
                </button>
              </div>
            </div>

            <!-- Message List -->
            <div v-for="(msg, idx) in messages" :key="idx" class="ai-msg-row" :class="msg.role">
              <div class="ai-msg-avatar" v-if="msg.role === 'assistant'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 8h1a4 4 0 1 1 0 8h-1"></path><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"></path><line x1="6" y1="2" x2="6" y2="4"></line><line x1="10" y1="2" x2="10" y2="4"></line><line x1="14" y1="2" x2="14" y2="4"></line></svg>
              </div>
              <div class="ai-msg-bubble-wrap">
                <div class="ai-msg-bubble" :class="msg.role">
                  <div v-if="msg.role === 'assistant'" class="ai-markdown-content" v-html="renderMarkdown(msg.content)"></div>
                  <div v-else class="ai-text-content">{{ msg.content }}</div>
                </div>
                <div class="ai-msg-meta">{{ msg.time }}</div>
              </div>
            </div>

            <!-- Typing indicator -->
            <div v-if="isTyping" class="ai-msg-row assistant">
              <div class="ai-msg-avatar">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 8h1a4 4 0 1 1 0 8h-1"></path><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"></path><line x1="6" y1="2" x2="6" y2="4"></line><line x1="10" y1="2" x2="10" y2="4"></line><line x1="14" y1="2" x2="14" y2="4"></line></svg>
              </div>
              <div class="ai-msg-bubble assistant typing">
                <div class="ai-typing-loader">
                  <span></span><span></span><span></span>
                </div>
              </div>
            </div>
          </div>

          <!-- Input Area -->
          <div class="ai-chat-footer">
            <div class="ai-input-wrapper">
              <input
                v-model="inputText"
                @keydown.enter="sendMessage"
                placeholder="Nhập câu hỏi của bạn..."
                :disabled="isTyping"
                ref="inputRef"
              />
              <button class="ai-send-button" @click="sendMessage" :disabled="!inputText.trim() || isTyping">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { marked } from 'marked'
import api from '@/services/api'

const router = useRouter()
const isOpen = ref(false)
const messages = ref([])
const inputText = ref('')
const isTyping = ref(false)
const sessionId = ref(sessionStorage.getItem('ai_session_id') || '')
const messagesContainer = ref(null)
const inputRef = ref(null)

const suggestions = [
  { 
    text: 'Báo cáo tình hình hôm nay', 
    icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>'
  },
  { 
    text: 'Tình hình ca làm việc hiện tại', 
    icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>'
  },
  { 
    text: 'Kiểm tra kho nguyên liệu', 
    icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>'
  },
  { 
    text: 'Top món bán chạy tuần này', 
    icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path><path d="M4 22h16"></path><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"></path></svg>'
  },
  { 
    text: 'Gợi ý cải thiện doanh thu', 
    icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A5 5 0 0 0 8 8c0 1.3.5 2.6 1.5 3.5.8.8 1.3 1.5 1.5 2.5"></path><path d="M9 18h6"></path><path d="M10 22h4"></path></svg>'
  },
]

// Configure marked with custom renderer for action links
marked.use({
  renderer: {
    link({ href, title, text }) {
      if (href && href.startsWith('action:')) {
        const route = href.replace('action:', '')
        return `<button class="ai-action-btn" data-route="${route}">${text}</button>`
      }
      return `<a href="${href}" title="${title || ''}" target="_blank">${text}</a>`
    }
  },
  breaks: true,
  gfm: true,
})

function renderMarkdown(text) {
  return marked.parse(text || '')
}

// Handle clicks on action buttons (event delegation)
function handleChatClick(event) {
  const btn = event.target.closest('.ai-action-btn')
  if (btn) {
    const route = btn.getAttribute('data-route')
    if (route) {
      router.push(route)
      // Optional: close chat on navigate
      // isOpen.value = false
    }
  }
}

function toggleChat() {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    nextTick(() => {
      inputRef.value?.focus()
      scrollToBottom()
    })
  }
}

async function sendMessage() {
  const text = inputText.value.trim()
  if (!text || isTyping.value) return

  // Add user message
  messages.value.push({
    role: 'user',
    content: text,
    time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
  })
  inputText.value = ''
  isTyping.value = true
  await nextTick()
  scrollToBottom()

  try {
    const res = await api.aiChat({
      message: text,
      session_id: sessionId.value || undefined
    })

    if (res.data.success) {
      // Save session ID
      if (!sessionId.value && res.data.data.session_id) {
        sessionId.value = res.data.data.session_id
        sessionStorage.setItem('ai_session_id', sessionId.value)
      }

      messages.value.push({
        role: 'assistant',
        content: res.data.data.reply,
        time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
      })
    }
  } catch (e) {
    messages.value.push({
      role: 'assistant',
      content: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg> Xin lỗi, đã xảy ra lỗi kết nối. Vui lòng thử lại.',
      time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
    })
  } finally {
    isTyping.value = false
    await nextTick()
    scrollToBottom()
  }
}

function sendSuggestion(text) {
  inputText.value = text
  sendMessage()
}

async function clearChat() {
  if (sessionId.value) {
    try {
      await api.aiClearHistory({ session_id: sessionId.value })
    } catch (e) { /* ignore */ }
  }
  messages.value = []
  sessionId.value = ''
  sessionStorage.removeItem('ai_session_id')
}

function scrollToBottom() {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

// Load history on mount if session exists
onMounted(async () => {
  if (sessionId.value) {
    try {
      const res = await api.aiHistory({ session_id: sessionId.value })
      if (res.data.success && res.data.data.length > 0) {
        messages.value = res.data.data.map(msg => ({
          role: msg.role,
          content: msg.content,
          time: msg.created_at ? new Date(msg.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) : ''
        }))
      }
    } catch (e) { /* ignore */ }
  }
})
</script>

<style>
:root {
  --coffee-dark: #2C1810;
  --coffee-espresso: #1A0F0A;
  --coffee-accent: #D4A373;
  --coffee-latte: #FAEDCD;
  --coffee-cream: #FEFAE0;
  --coffee-text: #E9EDC9;
  --glass-bg: rgba(26, 15, 10, 0.85);
  --glass-border: rgba(212, 163, 115, 0.2);
  --shadow-premium: 0 20px 50px rgba(0, 0, 0, 0.4);
}

/* ===== Floating Action Button ===== */
.ai-fab {
  position: fixed;
  bottom: 24px;
  right: 24px;
  width: 64px;
  height: 64px;
  border-radius: 20px;
  border: 1px solid var(--glass-border);
  background: linear-gradient(135deg, var(--coffee-dark), var(--coffee-espresso));
  color: var(--coffee-accent);
  cursor: pointer;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.ai-fab:hover {
  transform: translateY(-5px) rotate(5deg);
  background: var(--coffee-dark);
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
  color: var(--coffee-latte);
}

.ai-fab.active {
  transform: scale(0.9) rotate(-90deg);
  background: var(--coffee-espresso);
  color: #ef4444;
}

.ai-fab-ring {
  position: absolute;
  inset: -4px;
  border-radius: 24px;
  border: 2px solid var(--coffee-accent);
  opacity: 0.3;
  animation: fabRingPulse 2s infinite;
}

@keyframes fabRingPulse {
  0% { transform: scale(1); opacity: 0.3; }
  100% { transform: scale(1.2); opacity: 0; }
}

/* ===== Chat Panel ===== */
.ai-chat-panel {
  position: fixed;
  bottom: 100px;
  right: 24px;
  width: 440px;
  max-width: calc(100vw - 32px);
  height: 680px;
  max-height: calc(100vh - 140px);
  background: var(--glass-bg);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid var(--glass-border);
  border-radius: 28px;
  display: flex;
  flex-direction: column;
  z-index: 999;
  box-shadow: var(--shadow-premium);
  overflow: hidden;
  color: var(--coffee-cream);
}

/* Animations */
.chat-panel-fade-enter-active { animation: chatReveal 0.4s cubic-bezier(0.22, 1, 0.36, 1); }
.chat-panel-fade-leave-active { animation: chatReveal 0.3s cubic-bezier(0.22, 1, 0.36, 1) reverse; }

@keyframes chatReveal {
  from { opacity: 0; transform: translateY(30px) scale(0.96); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

/* ===== Header ===== */
.ai-chat-header {
  padding: 20px 24px;
  background: rgba(44, 24, 16, 0.4);
  border-bottom: 1px solid var(--glass-border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.ai-header-main {
  display: flex;
  align-items: center;
  gap: 14px;
}

.ai-avatar-wrapper {
  position: relative;
  width: 44px;
  height: 44px;
}

.ai-avatar-inner {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, var(--coffee-accent), #8b5e34);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.ai-online-dot {
  position: absolute;
  bottom: -2px;
  right: -2px;
  width: 12px;
  height: 12px;
  background: #10b981;
  border: 2px solid var(--coffee-espresso);
  border-radius: 50%;
}

.ai-title-group h4 {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: var(--coffee-latte);
}

.ai-status-row {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 2px;
}

.ai-status-text {
  font-size: 0.72rem;
  color: #94a3b8;
}

.ai-pulse-dot {
  width: 6px;
  height: 6px;
  background: #10b981;
  border-radius: 50%;
  animation: statusPulse 1.5s infinite;
}

@keyframes statusPulse {
  0% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.5); opacity: 0.5; }
  100% { transform: scale(1); opacity: 1; }
}

.ai-header-actions {
  display: flex;
  gap: 8px;
}

.ai-action-icon {
  width: 36px;
  height: 36px;
  border: none;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  color: var(--coffee-accent);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.ai-action-icon:hover {
  background: rgba(255, 255, 255, 0.1);
  color: var(--coffee-latte);
  transform: translateY(-2px);
}

/* ===== Messages Area ===== */
.ai-chat-body {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 20px;
  scroll-behavior: smooth;
}

.ai-chat-body::-webkit-scrollbar { width: 5px; }
.ai-chat-body::-webkit-scrollbar-thumb { background: var(--glass-border); border-radius: 10px; }

/* Welcome State */
.ai-welcome-container {
  padding: 10px 0;
}

.ai-welcome-hero {
  text-align: center;
  margin-bottom: 30px;
}

.ai-hero-icon {
  font-size: 50px;
  margin-bottom: 16px;
  filter: drop-shadow(0 5px 15px rgba(212, 163, 115, 0.4));
}

.ai-welcome-hero h2 {
  font-size: 1.4rem;
  font-weight: 700;
  margin-bottom: 10px;
  color: var(--coffee-latte);
}

.ai-welcome-hero p {
  font-size: 0.9rem;
  color: #94a3b8;
  line-height: 1.6;
}

.ai-suggestion-grid {
  display: grid;
  gap: 12px;
}

.ai-suggestion-card {
  padding: 14px 18px;
  background: rgba(212, 163, 115, 0.08);
  border: 1px solid var(--glass-border);
  border-radius: 16px;
  color: var(--coffee-accent);
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  text-align: left;
}

.ai-suggestion-card:hover {
  background: rgba(212, 163, 115, 0.15);
  border-color: var(--coffee-accent);
  transform: translateX(6px);
}

.ai-card-text { font-size: 0.88rem; font-weight: 500; }
.ai-card-arrow { opacity: 0; transition: 0.3s; }
.ai-suggestion-card:hover .ai-card-arrow { opacity: 1; }

/* Message Rows */
.ai-msg-row {
  display: flex;
  gap: 12px;
  max-width: 90%;
}

.ai-msg-row.assistant { align-self: flex-start; }
.ai-msg-row.user {
  align-self: flex-end;
  flex-direction: row-reverse;
}

.ai-msg-avatar {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  background: var(--coffee-dark);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  flex-shrink: 0;
  border: 1px solid var(--glass-border);
}

.ai-msg-bubble-wrap {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.ai-msg-bubble {
  padding: 12px 18px;
  border-radius: 20px;
  font-size: 0.92rem;
  line-height: 1.6;
  position: relative;
}

.ai-msg-bubble.assistant {
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid var(--glass-border);
  color: var(--coffee-cream);
  border-top-left-radius: 4px;
}

.ai-msg-bubble.user {
  background: linear-gradient(135deg, var(--coffee-accent), #8b5e34);
  color: var(--coffee-espresso);
  font-weight: 500;
  border-top-right-radius: 4px;
  box-shadow: 0 4px 15px rgba(212, 163, 115, 0.2);
}

.ai-msg-meta {
  font-size: 0.68rem;
  color: #64748b;
  margin: 0 4px;
}
.user .ai-msg-meta { text-align: right; }

/* Markdown Styles */
.ai-markdown-content h1, .ai-markdown-content h2, .ai-markdown-content h3 {
  color: var(--coffee-accent);
  margin: 14px 0 8px;
  font-size: 1rem;
}
.ai-markdown-content p { margin: 8px 0; }
.ai-markdown-content strong { color: var(--coffee-latte); }
.ai-markdown-content ul, .ai-markdown-content ol { padding-left: 20px; margin: 8px 0; }

.ai-markdown-content table {
  width: 100%;
  background: rgba(0,0,0,0.2);
  border-radius: 12px;
  overflow: hidden;
  margin: 12px 0;
  font-size: 0.85rem;
}
.ai-markdown-content th {
  background: rgba(212, 163, 115, 0.15);
  color: var(--coffee-accent);
  padding: 10px;
  text-align: left;
}
.ai-markdown-content td {
  padding: 8px 10px;
  border-top: 1px solid rgba(255,255,255,0.05);
}

/* Quick Action Buttons */
.ai-action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin: 6px 4px 0 0;
  padding: 8px 14px;
  background: rgba(212, 163, 115, 0.1);
  border: 1px solid var(--coffee-accent);
  border-radius: 12px;
  color: var(--coffee-accent);
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.ai-action-btn:hover {
  background: var(--coffee-accent);
  color: var(--coffee-espresso);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(212, 163, 115, 0.3);
}

.ai-card-content {
  display: flex;
  align-items: center;
  gap: 12px;
}

.ai-card-icon {
  color: var(--coffee-accent);
  display: flex;
  align-items: center;
}

/* Typing Indicator */
.ai-typing-loader {
  display: flex;
  gap: 4px;
  padding: 4px 0;
}
.ai-typing-loader span {
  width: 6px;
  height: 6px;
  background: var(--coffee-accent);
  border-radius: 50%;
  animation: typeAnim 1.4s infinite ease-in-out both;
}
.ai-typing-loader span:nth-child(2) { animation-delay: 0.2s; }
.ai-typing-loader span:nth-child(3) { animation-delay: 0.4s; }

@keyframes typeAnim {
  0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
  40% { transform: scale(1); opacity: 1; }
}

/* ===== Footer ===== */
.ai-chat-footer {
  padding: 20px 24px 24px;
  background: rgba(26, 15, 10, 0.5);
  border-top: 1px solid var(--glass-border);
}

.ai-input-wrapper {
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid var(--glass-border);
  border-radius: 18px;
  padding: 6px 6px 6px 16px;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: all 0.3s;
}

.ai-input-wrapper:focus-within {
  border-color: var(--coffee-accent);
  background: rgba(255, 255, 255, 0.09);
  box-shadow: 0 0 0 4px rgba(212, 163, 115, 0.1);
}

.ai-input-wrapper input {
  flex: 1;
  background: none;
  border: none;
  outline: none;
  color: var(--coffee-cream);
  font-size: 0.92rem;
  padding: 10px 0;
}

.ai-input-wrapper input::placeholder { color: #64748b; }

.ai-send-button {
  width: 42px;
  height: 42px;
  background: var(--coffee-accent);
  border: none;
  border-radius: 14px;
  color: var(--coffee-espresso);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.ai-send-button:hover:not(:disabled) {
  transform: scale(1.05) rotate(-5deg);
  background: var(--coffee-latte);
}

.ai-send-button:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

/* Mobile adjustments */
@media (max-width: 500px) {
  .ai-chat-panel {
    width: 100vw;
    height: 100vh;
    bottom: 0;
    right: 0;
    border-radius: 0;
    max-width: none;
    max-height: none;
  }
  .ai-fab { bottom: 16px; right: 16px; }
}
</style>
