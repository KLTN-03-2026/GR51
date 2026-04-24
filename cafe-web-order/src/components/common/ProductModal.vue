<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  isOpen: Boolean,
  item: Object,
  toppings: {
    type: Array,
    default: () => []
  },
  sizes: {
    type: Array,
    default: () => []
  },
  editItem: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'add-to-cart'])

const quantity = ref(1)
const selectedToppingIds = ref([])
const selectedSizeId = ref(null)
const note = ref('')

// Reset or populate state when modal opens
watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    if (props.editItem) {
      quantity.value = props.editItem.quantity || 1
      selectedToppingIds.value = props.editItem._rawOptions ? [...props.editItem._rawOptions.toppingIds] : []
      selectedSizeId.value = props.editItem._rawOptions ? props.editItem._rawOptions.sizeId : null
      note.value = props.editItem._rawOptions ? props.editItem._rawOptions.noteText : ''
    } else {
      quantity.value = 1
      selectedToppingIds.value = []
      
      if (props.sizes && props.sizes.length > 0) {
        const defaultSize = props.sizes.find(s => Number(s.gia_cong_them) === 0) || props.sizes[0]
        selectedSizeId.value = defaultSize.ma_kich_co
      } else {
        selectedSizeId.value = null
      }

      note.value = ''
    }
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

const formatPrice = (price) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Number(price)).replace('₫', 'đ')
}

const toggleTopping = (toppingId) => {
  const index = selectedToppingIds.value.indexOf(toppingId)
  if (index === -1) {
    selectedToppingIds.value.push(toppingId)
  } else {
    selectedToppingIds.value.splice(index, 1)
  }
}

const totalPrice = computed(() => {
  if (!props.item) return 0
  
  let basePrice = Number(props.item.gia_ban)
  
  let sizePrice = 0
  if (selectedSizeId.value) {
    const s = props.sizes.find(x => x.ma_kich_co === selectedSizeId.value)
    if (s) sizePrice += Number(s.gia_cong_them)
  }
  
  let toppingPrice = 0
  for (const tid of selectedToppingIds.value) {
    const t = props.toppings.find(x => x.ma_topping === tid)
    if (t) toppingPrice += Number(t.gia_tien)
  }
  
  return (basePrice + sizePrice + toppingPrice) * quantity.value
})

const addToCart = () => {
  const selectedSizeObj = props.sizes.find(s => s.ma_kich_co === selectedSizeId.value)
  const selectedToppingObjs = selectedToppingIds.value.map(tid => props.toppings.find(t => t.ma_topping === tid))
  
  let fullNote = note.value
  let addons = []
  
  if (selectedSizeObj) {
    addons.push(`Size: ${selectedSizeObj.ten_kich_co}`)
  }
  
  if (selectedToppingObjs.length > 0) {
    addons.push(`Topping: ${selectedToppingObjs.map(t => t.ten_topping).join(', ')}`)
  }
  
  if (addons.length > 0) {
     fullNote = fullNote ? `${addons.join(' | ')}. ${fullNote}` : addons.join(' | ')
  }

  const payload = {
    ...props.item,
    quantity: quantity.value,
    gia_ban: totalPrice.value / quantity.value, // store per-unit price including toppings
    ghi_chu: fullNote,
    _rawOptions: {
      sizeId: selectedSizeId.value,
      toppingIds: [...selectedToppingIds.value],
      noteText: note.value
    },
    cartItemId: props.editItem && props.editItem.cartItemId ? props.editItem.cartItemId : Date.now().toString() + Math.random().toString()
  }
  
  emit('add-to-cart', payload, !!props.editItem)
  emit('close')
}

const close = () => {
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <Transition name="slide-up">
      <div v-if="isOpen" class="modal-overlay" @click.self="close">
        <div class="modal-content">
          <!-- Close Handle -->
          <div class="drag-handle" @click="close">
            <div class="handle-bar"></div>
          </div>
          
          <button class="close-btn" @click="close">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>

          <div class="item-header" v-if="item">
            <img :src="item.hinh_anh && item.hinh_anh !== 'default.jpg' ? (item.hinh_anh.startsWith('http') ? item.hinh_anh : 'http://localhost:8000/storage/' + item.hinh_anh) : 'https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&q=80&w=400&h=300'" :alt="item.ten_mon" class="item-image" />
            <div class="item-title">
              <h3>{{ item.ten_mon }}</h3>
              <p class="base-price">{{ formatPrice(item.gia_ban) }}</p>
            </div>
          </div>

          <div class="modal-body scrollable">
          <!-- Sizes -->
          <div class="section" v-if="sizes && sizes.length > 0">
            <h4 class="section-title">Chọn kích cỡ</h4>
            <div class="size-list">
              <label 
                v-for="s in sizes" 
                :key="s.ma_kich_co"
                class="size-item"
                :class="{ active: selectedSizeId === s.ma_kich_co }"
              >
                <input type="radio" :value="s.ma_kich_co" v-model="selectedSizeId" class="hidden-radio">
                <span class="size-name">{{ s.ten_kich_co }}</span>
                <span class="size-price" v-if="Number(s.gia_cong_them) > 0">+{{ formatPrice(s.gia_cong_them) }}</span>
                <span class="size-price" v-else>+0đ</span>
              </label>
            </div>
          </div>

          <!-- Toppings -->
            <div class="section" v-if="toppings.length > 0">
              <h4 class="section-title">Thêm topping (tùy chọn)</h4>
              <div class="topping-list">
                <label 
                  v-for="t in toppings" 
                  :key="t.ma_topping"
                  class="topping-item"
                >
                  <div class="topping-left">
                    <input type="checkbox" :value="t.ma_topping" @change="toggleTopping(t.ma_topping)" class="checkbox-custom">
                    <span>{{ t.ten_topping }}</span>
                  </div>
                  <span class="topping-price">+{{ formatPrice(t.gia_tien) }}</span>
                </label>
              </div>
            </div>

            <!-- Note -->
            <div class="section">
              <h4 class="section-title">Ghi chú</h4>
              <textarea 
                v-model="note" 
                class="note-input" 
                placeholder="VD: Ít đá, ít ngọt..."
                rows="2"
              ></textarea>
            </div>

            <!-- Quantity -->
            <div class="section quantity-section">
              <h4 class="section-title">Số lượng</h4>
              <div class="qty-control">
                <button class="qty-btn" @click="quantity > 1 ? quantity-- : null">-</button>
                <span class="qty-text">{{ quantity }}</span>
                <button class="qty-btn" @click="quantity++">+</button>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="modal-footer">
            <button class="add-btn" @click="addToCart">
              <span>{{ editItem ? 'Cập nhật' : 'Thêm vào giỏ' }}</span>
              <span class="total-price">{{ formatPrice(totalPrice) }}</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background-color: rgba(0, 0, 0, 0.4);
  z-index: 1000;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.modal-content {
  background-color: var(--color-bg);
  width: 100%;
  max-width: 480px;
  border-top-left-radius: 24px;
  border-top-right-radius: 24px;
  display: flex;
  flex-direction: column;
  max-height: 90vh;
  position: relative;
}

.drag-handle {
  display: flex;
  justify-content: center;
  padding: 12px 0;
}

.handle-bar {
  width: 40px;
  height: 4px;
  background-color: #E0E0E0;
  border-radius: 2px;
}

.close-btn {
  position: absolute;
  top: 16px;
  right: 16px;
  background: white;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: var(--shadow-sm);
  color: var(--color-text-main);
  z-index: 2;
}

.item-header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 0 20px 20px;
  border-bottom: 1px solid var(--color-border);
}

.item-image {
  width: 80px;
  height: 80px;
  border-radius: 12px;
  object-fit: cover;
}

.item-title h3 {
  font-size: 20px;
  margin-bottom: 4px;
  color: var(--color-text-brown);
}

.base-price {
  font-weight: 500;
  color: var(--color-text-light);
}

.modal-body {
  overflow-y: auto;
  padding: 20px;
  flex: 1;
}

.section {
  margin-bottom: 24px;
}

.section-title {
  font-family: var(--font-body);
  font-size: 16px;
  margin-bottom: 12px;
  color: var(--color-text-main);
}

.topping-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.topping-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  background-color: var(--color-white);
  cursor: pointer;
  transition: border-color 0.2s;
}

.topping-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

/* Custom Checkbox as Circle */
.checkbox-custom {
  appearance: none;
  width: 20px;
  height: 20px;
  border: 1px solid #CCC;
  border-radius: 50%;
  position: relative;
  outline: none;
  cursor: pointer;
}

.checkbox-custom:checked {
  border-color: var(--color-primary);
}

.checkbox-custom:checked::after {
  content: '';
  position: absolute;
  top: 4px; left: 4px;
  width: 10px; height: 10px;
  background-color: var(--color-primary);
  border-radius: 50%;
}

.topping-price {
  font-weight: 500;
  color: var(--color-text-brown);
}

.size-list {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.size-item {
  flex: 1;
  min-width: 100px;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 12px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  flex-direction: column;
  gap: 4px;
  background-color: var(--color-white);
}

.size-item.active {
  border-color: var(--color-primary);
  background-color: rgba(110, 68, 35, 0.05);
}

.size-item.active .size-name {
  color: var(--color-primary);
  font-weight: 600;
}

.size-name {
  font-size: 15px;
  color: var(--color-text-main);
  font-weight: 500;
}

.size-price {
  font-size: 13px;
  color: var(--color-text-light);
}

.hidden-radio {
  display: none;
}

.note-input {
  width: 100%;
  padding: 12px;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  font-family: var(--font-body);
  resize: none;
  outline: none;
  background-color: var(--color-white);
}

.note-input:focus {
  border-color: var(--color-primary);
}

.quantity-section {
  margin-bottom: 10px;
}

.qty-control {
  display: flex;
  align-items: center;
  gap: 16px;
  display: inline-flex;
}

.qty-btn {
  width: 40px;
  height: 40px;
  border-radius: 20px;
  background-color: #F3F0EC;
  font-size: 24px;
  display: flex;
  justify-content: center;
  align-items: center;
  color: var(--color-text-brown);
}

.qty-btn:active {
  background-color: #E0DCD6;
}

.qty-text {
  font-size: 18px;
  font-weight: 600;
  min-width: 24px;
  text-align: center;
}

.modal-footer {
  padding: 16px 20px 24px;
  background-color: var(--color-bg);
  box-shadow: 0 -4px 12px rgba(0,0,0,0.03);
}

.add-btn {
  width: 100%;
  background-color: var(--color-primary);
  color: white;
  padding: 16px 20px;
  border-radius: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 16px;
  font-weight: 600;
}

.add-btn:active {
  background-color: var(--color-primary-dark);
}

.total-price {
  font-family: var(--font-heading);
  font-size: 18px;
  font-weight: 700;
}

/* Animations */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease-out;
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
}

.slide-up-enter-from .modal-content,
.slide-up-leave-to .modal-content {
  transform: translateY(100%);
}
</style>
