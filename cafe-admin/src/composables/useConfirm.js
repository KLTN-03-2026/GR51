import { reactive } from 'vue'

const state = reactive({
  show: false,
  title: '',
  message: '',
  resolve: null,
})

export function useConfirm() {
  function confirm(message, title = 'Xác nhận') {
    state.show = true
    state.title = title
    state.message = message
    return new Promise((resolve) => {
      state.resolve = resolve
    })
  }

  function accept() {
    state.show = false
    state.resolve?.(true)
  }

  function cancel() {
    state.show = false
    state.resolve?.(false)
  }

  return { state, confirm, accept, cancel }
}
