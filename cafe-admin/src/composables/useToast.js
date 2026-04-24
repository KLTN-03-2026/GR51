import { reactive } from 'vue'

const toasts = reactive([])
let id = 0

function addToast(message, type = 'info', duration = 3000) {
  const toast = { id: ++id, message, type, visible: true }
  toasts.push(toast)
  setTimeout(() => removeToast(toast.id), duration)
  return toast.id
}

function removeToast(toastId) {
  const idx = toasts.findIndex(t => t.id === toastId)
  if (idx > -1) toasts.splice(idx, 1)
}

export function useToast() {
  return {
    toasts,
    success: (msg) => addToast(msg, 'success'),
    error: (msg) => addToast(msg, 'error', 4000),
    warning: (msg) => addToast(msg, 'warning', 3500),
    info: (msg) => addToast(msg, 'info'),
    remove: removeToast,
  }
}
