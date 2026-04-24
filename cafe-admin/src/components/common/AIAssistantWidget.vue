<template>
  <div class="ai-assistant">
    <!-- Floating Button -->
    <button class="ai-fab" @click="toggleChat" :class="{ active: isOpen }">
      <span class="ai-fab-icon" v-if="!isOpen">✨</span>
      <span class="ai-fab-icon" v-else>✕</span>
      <span class="ai-fab-pulse" v-if="!isOpen"></span>
    </button>

    <!-- Chat Panel -->
    <Teleport to="body">
      <transition name="chat-slide">
        <div v-if="isOpen" class="ai-chat-panel">
          <!-- Header -->
          <div class="ai-chat-header">
            <div class="ai-header-info">
              <div class="ai-avatar">🤖</div>
              <div>
                <h4>Cafe AI</h4>
                <span class="ai-status">Trợ lý thông minh</span>
              </div>
            </div>
            <div class="ai-header-actions">
              <button class="ai-btn-icon" @click="clearChat" title="Xóa hội thoại">🗑️</button>
              <button class="ai-btn-icon" @click="toggleChat">✕</button>
            </div>
          </div>

          <!-- Messages -->
          <div class="ai-chat-messages" ref="messagesContainer">
            <!-- Welcome -->
            <div v-if="messages.length === 0" class="ai-welcome">
              <div class="ai-welcome-icon">🤖</div>
              <h3>Xin chào, Sếp! 👋</h3>
              <p>Tôi là <strong>Cafe AI</strong> — trợ lý quản lý thông minh. Hãy hỏi tôi bất cứ điều gì về quán!</p>
              <div class="ai-suggestions">
                <button v-for="s in suggestions" :key="s" class="ai-suggestion-btn" @click="sendSuggestion(s)">{{ s }}</button>
              </div>
            </div>

            <!-- Messages list -->
            <div v-for="(msg, idx) in messages" :key="idx" class="ai-message" :class="msg.role">
              <div class="ai-msg-avatar" v-if="msg.role === 'assistant'">🤖</div>
              <div class="ai-msg-bubble" :class="msg.role">
                <div v-if="msg.role === 'assistant'" class="ai-msg-content" v-html="renderMarkdown(msg.content)"></div>
                <div v-else class="ai-msg-content">{{ msg.content }}</div>
                <span class="ai-msg-time">{{ msg.time }}</span>
              </div>
            </div>

            <!-- Typing indicator -->
            <div v-if="isTyping" class="ai-message assistant">
              <div class="ai-msg-avatar">🤖</div>
              <div class="ai-msg-bubble assistant typing">
                <div class="ai-typing-dots">
                  <span></span><span></span><span></span>
                </div>
              </div>
            </div>
          </div>

          <!-- Input -->
          <div class="ai-chat-input">
            <input
              v-model="inputText"
              @keydown.enter="sendMessage"
              placeholder="Hỏi Cafe AI..."
              :disabled="isTyping"
              ref="inputRef"
            />
            <button class="ai-send-btn" @click="sendMessage" :disabled="!inputText.trim() || isTyping">
              <span>➤</span>
            </button>
          </div>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted, watch } from 'vue'
import { marked } from 'marked'
import api from '@/services/api'

const isOpen = ref(false)
const messages = ref([])
const inputText = ref('')
const isTyping = ref(false)
const sessionId = ref(sessionStorage.getItem('ai_session_id') || '')
const messagesContainer = ref(null)
const inputRef = ref(null)

const suggestions = [
  '📊 Báo cáo tình hình hôm nay',
  '📦 Kiểm tra kho nguyên liệu',
  '🏆 Top món bán chạy tuần này',
  '📈 Phân tích Menu Engineering',
  '💡 Gợi ý cải thiện doanh thu',
]

// Configure marked
marked.setOptions({
  breaks: true,
  gfm: true,
})

function renderMarkdown(text) {
  return marked.parse(text || '')
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
      content: '⚠️ Xin lỗi, đã xảy ra lỗi kết nối. Vui lòng thử lại.',
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
/* ===== Floating Action Button ===== */
.ai-fab {
  position: fixed;
  bottom: 24px;
  right: 24px;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  border: none;
  background: linear-gradient(135deg, #f59e0b, #ef4444, #8b5cf6);
  color: white;
  font-size: 24px;
  cursor: pointer;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 20px rgba(245, 158, 11, 0.4);
  transition: all 0.3s ease;
}
.ai-fab:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 30px rgba(245, 158, 11, 0.6);
}
.ai-fab.active {
  background: var(--bg-tertiary, #334155);
  box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}
.ai-fab-icon {
  transition: transform 0.3s ease;
}
.ai-fab-pulse {
  position: absolute;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: linear-gradient(135deg, #f59e0b, #ef4444);
  animation: fabPulse 2s infinite;
  z-index: -1;
}
@keyframes fabPulse {
  0% { transform: scale(1); opacity: 0.5; }
  100% { transform: scale(1.5); opacity: 0; }
}

/* ===== Chat Panel ===== */
.ai-chat-panel {
  position: fixed;
  bottom: 92px;
  right: 24px;
  width: 420px;
  max-width: calc(100vw - 32px);
  height: 600px;
  max-height: calc(100vh - 120px);
  background: #0f172a;
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 20px;
  display: flex;
  flex-direction: column;
  z-index: 999;
  box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.05);
  overflow: hidden;
}

/* Transition */
.chat-slide-enter-active { animation: chatSlideIn 0.35s ease-out; }
.chat-slide-leave-active { animation: chatSlideIn 0.25s ease-in reverse; }
@keyframes chatSlideIn {
  from { opacity: 0; transform: translateY(20px) scale(0.95); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

/* ===== Header ===== */
.ai-chat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background: linear-gradient(135deg, rgba(245,158,11,0.15), rgba(139,92,246,0.15));
  border-bottom: 1px solid rgba(255,255,255,0.08);
  flex-shrink: 0;
}
.ai-header-info {
  display: flex;
  align-items: center;
  gap: 12px;
}
.ai-avatar {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: linear-gradient(135deg, #f59e0b, #8b5cf6);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}
.ai-chat-header h4 {
  color: #f1f5f9;
  font-size: 0.95rem;
  font-weight: 600;
  margin: 0;
}
.ai-status {
  color: #94a3b8;
  font-size: 0.75rem;
}
.ai-header-actions {
  display: flex;
  gap: 4px;
}
.ai-btn-icon {
  width: 32px;
  height: 32px;
  border: none;
  background: rgba(255,255,255,0.06);
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}
.ai-btn-icon:hover {
  background: rgba(255,255,255,0.12);
}

/* ===== Messages Area ===== */
.ai-chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  min-height: 0;
}
.ai-chat-messages::-webkit-scrollbar { width: 4px; }
.ai-chat-messages::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 2px; }

/* Welcome */
.ai-welcome {
  text-align: center;
  padding: 20px 10px;
}
.ai-welcome-icon {
  font-size: 48px;
  margin-bottom: 12px;
  animation: botBounce 2s infinite;
}
@keyframes botBounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}
.ai-welcome h3 {
  color: #f1f5f9;
  font-size: 1.1rem;
  margin-bottom: 8px;
}
.ai-welcome p {
  color: #94a3b8;
  font-size: 0.85rem;
  line-height: 1.5;
  margin-bottom: 20px;
}
.ai-suggestions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.ai-suggestion-btn {
  padding: 10px 16px;
  border: 1px solid rgba(245,158,11,0.3);
  background: rgba(245,158,11,0.08);
  color: #fbbf24;
  border-radius: 12px;
  font-size: 0.82rem;
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
}
.ai-suggestion-btn:hover {
  background: rgba(245,158,11,0.18);
  border-color: rgba(245,158,11,0.5);
  transform: translateX(4px);
}

/* Message Bubble */
.ai-message {
  display: flex;
  gap: 8px;
  align-items: flex-end;
}
.ai-message.user {
  justify-content: flex-end;
}
.ai-msg-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: linear-gradient(135deg, #f59e0b, #8b5cf6);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  flex-shrink: 0;
}
.ai-msg-bubble {
  max-width: 85%;
  padding: 12px 16px;
  border-radius: 16px;
  position: relative;
}
.ai-msg-bubble.user {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white;
  border-bottom-right-radius: 4px;
}
.ai-msg-bubble.assistant {
  background: #1e293b;
  color: #e2e8f0;
  border: 1px solid rgba(255,255,255,0.06);
  border-bottom-left-radius: 4px;
}
.ai-msg-content {
  font-size: 0.88rem;
  line-height: 1.6;
  word-break: break-word;
}

/* Markdown rendering inside AI messages */
.ai-msg-bubble.assistant .ai-msg-content h1,
.ai-msg-bubble.assistant .ai-msg-content h2,
.ai-msg-bubble.assistant .ai-msg-content h3,
.ai-msg-bubble.assistant .ai-msg-content h4 {
  color: #fbbf24;
  margin: 12px 0 6px;
  font-size: 0.95rem;
}
.ai-msg-bubble.assistant .ai-msg-content h1:first-child,
.ai-msg-bubble.assistant .ai-msg-content h2:first-child,
.ai-msg-bubble.assistant .ai-msg-content h3:first-child {
  margin-top: 0;
}
.ai-msg-bubble.assistant .ai-msg-content p {
  margin: 6px 0;
}
.ai-msg-bubble.assistant .ai-msg-content ul,
.ai-msg-bubble.assistant .ai-msg-content ol {
  padding-left: 20px;
  margin: 6px 0;
}
.ai-msg-bubble.assistant .ai-msg-content li {
  margin: 3px 0;
}
.ai-msg-bubble.assistant .ai-msg-content strong {
  color: #fbbf24;
}
.ai-msg-bubble.assistant .ai-msg-content code {
  background: rgba(255,255,255,0.08);
  padding: 1px 5px;
  border-radius: 4px;
  font-size: 0.83rem;
}
.ai-msg-bubble.assistant .ai-msg-content table {
  width: 100%;
  border-collapse: collapse;
  margin: 8px 0;
  font-size: 0.82rem;
}
.ai-msg-bubble.assistant .ai-msg-content th {
  background: rgba(245,158,11,0.15);
  color: #fbbf24;
  padding: 6px 10px;
  text-align: left;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  white-space: nowrap;
}
.ai-msg-bubble.assistant .ai-msg-content td {
  padding: 5px 10px;
  border-bottom: 1px solid rgba(255,255,255,0.05);
}
.ai-msg-time {
  display: block;
  font-size: 0.68rem;
  color: rgba(255,255,255,0.3);
  margin-top: 6px;
  text-align: right;
}

/* Typing dots */
.ai-msg-bubble.typing {
  padding: 14px 20px;
}
.ai-typing-dots {
  display: flex;
  gap: 5px;
  align-items: center;
}
.ai-typing-dots span {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #f59e0b;
  animation: typingDot 1.4s infinite ease-in-out both;
}
.ai-typing-dots span:nth-child(2) { animation-delay: 0.16s; }
.ai-typing-dots span:nth-child(3) { animation-delay: 0.32s; }
@keyframes typingDot {
  0%, 80%, 100% { transform: scale(0.4); opacity: 0.4; }
  40% { transform: scale(1); opacity: 1; }
}

/* ===== Input Bar ===== */
.ai-chat-input {
  display: flex;
  gap: 8px;
  padding: 16px 20px;
  background: #0f172a;
  border-top: 1px solid rgba(255,255,255,0.08);
  flex-shrink: 0;
}
.ai-chat-input input {
  flex: 1;
  background: #1e293b;
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 12px;
  padding: 12px 16px;
  color: #f1f5f9;
  font-size: 0.88rem;
  outline: none;
  transition: border-color 0.2s;
}
.ai-chat-input input:focus {
  border-color: #f59e0b;
  box-shadow: 0 0 0 3px rgba(245,158,11,0.15);
}
.ai-chat-input input::placeholder {
  color: #64748b;
}
.ai-send-btn {
  width: 44px;
  height: 44px;
  border: none;
  background: linear-gradient(135deg, #f59e0b, #ef4444);
  border-radius: 12px;
  color: white;
  font-size: 18px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  flex-shrink: 0;
}
.ai-send-btn:hover:not(:disabled) {
  transform: scale(1.05);
  box-shadow: 0 4px 15px rgba(245,158,11,0.4);
}
.ai-send-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* ===== Responsive ===== */
@media (max-width: 480px) {
  .ai-chat-panel {
    width: calc(100vw - 16px);
    right: 8px;
    bottom: 80px;
    height: calc(100vh - 100px);
    border-radius: 16px;
  }
  .ai-fab {
    bottom: 16px;
    right: 16px;
  }
}
</style>
