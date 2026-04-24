<script setup>
import { ref, onMounted, inject, computed } from "vue";
import api from "../services/api";
import ProductModal from "../components/common/ProductModal.vue";
import CartDrawer from "../components/common/CartDrawer.vue";

// We inject the cart from App.vue
const cart = inject("cart");
const tableCode = inject("tableCode");
const tableName = inject("tableName");

const emit = defineEmits(['submit-order']);

const loading = ref(true);
const menuItems = ref([]);
const categories = ref([]);
const activeCategory = ref("Tất cả");

const showModal = ref(false);
const selectedItem = ref(null);
const editItemData = ref(null);
const isCartDrawerOpen = ref(false);
const availableToppings = ref([]);
const availableSizes = ref([]);

// Toast state
const toastMessage = ref('');
const showToast = ref(false);
let toastTimeout = null;

const notify = (msg) => {
  toastMessage.value = msg;
  showToast.value = true;
  if (toastTimeout) clearTimeout(toastTimeout);
  toastTimeout = setTimeout(() => {
    showToast.value = false;
  }, 2500);
};

onMounted(async () => {
  loading.value = true;
  try {
    // Gọi API để lấy menu thật
    const response = await api.getMenu();
    if (response.data && response.data.data && response.data.data.danh_mucs) {
      const allItems = [];
      const cats = [];

      response.data.data.danh_mucs.forEach((danhMuc) => {
        cats.push(danhMuc.ten_danh_muc);
        if (danhMuc.mons) {
          danhMuc.mons.forEach((mon) => {
            allItems.push({
              ...mon,
              danh_muc: { ten_danh_muc: danhMuc.ten_danh_muc },
            });
          });
        }
      });

      menuItems.value = allItems;
      categories.value = ["Tất cả", ...new Set(cats)];
      
      if (response.data.data.toppings) {
        availableToppings.value = response.data.data.toppings;
      }
      if (response.data.data.sizes) {
        availableSizes.value = response.data.data.sizes;
      }
    }
  } catch (error) {
    console.error("Lỗi lấy menu:", error);
  } finally {
    loading.value = false;
  }
});

const filteredMenu = computed(() => {
  if (activeCategory.value === "Tất cả") return menuItems.value;
  return menuItems.value.filter(
    (item) => (item.danh_muc?.ten_danh_muc || "Khác") === activeCategory.value,
  );
});

const formatPrice = (price) => {
  return new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND" })
    .format(Number(price))
    .replace("₫", "đ");
};

const openModal = (item) => {
  editItemData.value = null; // Tắt chế độ edit
  selectedItem.value = item;
  showModal.value = true;
};

const handleAddToCart = (payload, isEdit) => {
  if (isEdit) {
    const index = cart.value.findIndex(i => i.cartItemId === payload.cartItemId);
    if (index !== -1) {
      cart.value[index] = payload;
    }
    notify(`Đã cập nhật ${payload.ten_mon}!`);
    return;
  }

  // Check if exactly same item (with same toppings/notes) exists
  const existing = cart.value.find((i) => i.ma_mon === payload.ma_mon && i.ghi_chu === payload.ghi_chu && !i.cartItemId); // Nếu cũ ko có ID
  if (existing) {
    existing.quantity += payload.quantity;
  } else {
    cart.value.push(payload);
  }

  notify(`Đã thêm ${payload.quantity} ${payload.ten_mon} vào giỏ hàng!`);
};

// --- Cart Drawer Methods ---
const openEditItem = (item) => {
  // Tìm item gốc trong menuItems để có đẩy đủ thông tin (vì cart item có thể thiếu field)
  const baseItem = menuItems.value.find(i => i.ma_mon === item.ma_mon) || item;
  selectedItem.value = baseItem;
  editItemData.value = item;
  isCartDrawerOpen.value = false;
  showModal.value = true;
};

const updateCartQuantity = ({ item, delta }) => {
  const existing = cart.value.find(i => i.cartItemId === item.cartItemId);
  if (existing) {
    existing.quantity += delta;
    if (existing.quantity <= 0) {
      removeCartItem(item);
    }
  }
};

const removeCartItem = (item) => {
  const index = cart.value.findIndex(i => i.cartItemId === item.cartItemId);
  if (index !== -1) {
    cart.value.splice(index, 1);
  }
};

const submitOrder = (paymentMethod) => {
  isCartDrawerOpen.value = false;
  emit('submit-order', paymentMethod);
};
</script>

<template>
  <div class="menu-view animate-fade-in">
    <!-- Header -->
    <header class="top-header">
      <div class="logo">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="28"
          height="28"
          viewBox="0 0 24 24"
          fill="none"
          stroke="var(--color-primary)"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="M17 8h1a4 4 0 1 1 0 8h-1"></path>
          <path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"></path>
          <line x1="6" y1="2" x2="6" y2="4"></line>
          <line x1="10" y1="2" x2="10" y2="4"></line>
          <line x1="14" y1="2" x2="14" y2="4"></line>
        </svg>
        <div class="brand-text">
          <h1 class="brand-name">Gunpla Coffe</h1>
          <p class="brand-slogan">Nơi hương vị thăng hoa</p>
          <p class="brand-slogan">Nơi đam mê hội tụ</p>
        </div>
      </div>
      <div v-if="tableCode" class="table-badge">
        {{ tableName || "Bàn " + tableCode }}
      </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
      <!-- Use a generic coffee shop image as background -->
      <div
        class="hero-bg"
        style="
          background-image: url(&quot;https://images.unsplash.com/photo-1554118811-1e0d58224f24?auto=format&fit=crop&q=80&w=600&h=300&quot;);
        "
      ></div>
      <div class="hero-overlay"></div>
      <div class="hero-content">
        <h2>Đặt hàng ngay</h2>
        <p>Quét mã QR và thưởng thức</p>
      </div>
    </section>

    <!-- Category Filter -->
    <section class="category-section">
      <div class="category-scroll">
        <button
          v-for="cat in categories"
          :key="cat"
          class="cat-btn"
          :class="{ active: activeCategory === cat }"
          @click="activeCategory = cat"
        >
          {{ cat }}
        </button>
      </div>
    </section>

    <!-- Product List -->
    <section class="product-list">
      <div v-if="loading" class="loading-state">Đang tải thực đơn...</div>

      <div
        v-else
        v-for="item in filteredMenu"
        :key="item.ma_mon"
        class="product-card"
      >
        <div class="product-img">
          <!-- Fallback image logic if item.hinh_anh is missing -->
          <img
            :src="
              item.hinh_anh && item.hinh_anh !== 'default.jpg'
                ? item.hinh_anh.startsWith('http')
                  ? item.hinh_anh
                  : 'http://localhost:8000/storage/' + item.hinh_anh
                : 'https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&q=80&w=400&h=300'
            "
            :alt="item.ten_mon"
          />
        </div>
        <div class="product-info">
          <h3 class="product-title">{{ item.ten_mon }}</h3>
          <p class="product-desc">
            {{ item.mo_ta || "Hương vị thơm ngon, đánh thức ngày mới." }}
          </p>
          <div class="product-action">
            <span class="product-price">{{ formatPrice(item.gia_ban) }}</span>
            <button class="add-btn" @click="openModal(item)">Thêm</button>
          </div>
        </div>
      </div>

      <div v-if="!loading && filteredMenu.length === 0" class="empty-state">
        Không tìm thấy món ăn nào.
      </div>
    </section>

    <!-- Floating Action Button -->
    <Teleport to="body">
      <button 
        class="fab-cart" 
        v-if="cart.length > 0" 
        @click="isCartDrawerOpen = true"
      >
        <div class="fab-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          <span class="fab-badge">{{ cart.reduce((sum, item) => sum + item.quantity, 0) }}</span>
        </div>
      </button>
    </Teleport>

    <!-- Product Customization Modal -->
    <ProductModal 
      :is-open="showModal"
      :item="selectedItem"
      :edit-item="editItemData"
      :toppings="availableToppings"
      :sizes="availableSizes"
      @close="showModal = false"
      @add-to-cart="handleAddToCart"
    />

    <!-- Cart Drawer Modal -->
    <CartDrawer
      :is-open="isCartDrawerOpen"
      :cart="cart"
      @close="isCartDrawerOpen = false"
      @edit="openEditItem"
      @update-quantity="updateCartQuantity"
      @remove="removeCartItem"
      @submit-order="submitOrder"
    />

    <!-- Toast Notification -->
    <Teleport to="body">
      <Transition name="toast">
        <div v-if="showToast" class="toast-notification">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
          <span>{{ toastMessage }}</span>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.menu-view {
  padding-bottom: 20px;
}

/* Header */
.top-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background-color: var(--color-bg);
  position: sticky;
  top: 0;
  z-index: 10;
}

.logo {
  display: flex;
  align-items: center;
  gap: 12px;
}

.brand-text {
  display: flex;
  flex-direction: column;
}

.brand-name {
  font-size: 24px;
  line-height: 1.1;
  margin: 0;
}

.brand-slogan {
  font-size: 12px;
  color: #a08c7c;
  margin: 0;
}

.table-badge {
  background-color: var(--color-primary);
  color: white;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
}

/* Hero Section */
.hero-section {
  position: relative;
  height: 160px;
  margin: 0 20px;
  border-radius: var(--border-radius-lg);
  overflow: hidden;
  margin-bottom: 24px;
}

.hero-bg {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-size: cover;
  background-position: center;
}

.hero-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(
    to bottom,
    rgba(0, 0, 0, 0.1) 0%,
    rgba(253, 251, 247, 0.9) 100%
  );
}

.hero-content {
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  transform: translateY(-50%);
  text-align: center;
  z-index: 2;
  color: white;
}

.hero-content h2 {
  font-size: 28px;
  color: white;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
  margin-bottom: 4px;
}

.hero-content p {
  font-size: 14px;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
  opacity: 0.9;
}

/* Categories */
.category-section {
  margin-bottom: 24px;
}

.category-scroll {
  display: flex;
  overflow-x: auto;
  gap: 12px;
  padding: 0 20px;
  scrollbar-width: none; /* Firefox */
}

.category-scroll::-webkit-scrollbar {
  display: none; /* Safari/Chrome */
}

.cat-btn {
  padding: 10px 24px;
  border-radius: var(--border-radius-pill);
  background-color: var(--color-white);
  color: var(--color-text-brown);
  font-weight: 500;
  font-size: 15px;
  white-space: nowrap;
  box-shadow: 0 2px 8px rgba(110, 68, 35, 0.05);
  transition: all 0.2s ease;
}

.cat-btn.active {
  background-color: var(--color-primary);
  color: var(--color-white);
  box-shadow: 0 4px 12px rgba(110, 68, 35, 0.2);
}

/* Product List */
.product-list {
  padding: 0 20px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.product-card {
  background-color: var(--color-white);
  border-radius: var(--border-radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  transition: transform 0.2s ease;
}

.product-card:active {
  transform: scale(0.98);
}

.product-img {
  width: 100%;
  height: 200px;
  overflow: hidden;
}

.product-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.product-card:hover .product-img img {
  transform: scale(1.05);
}

.product-info {
  padding: 16px 20px 20px;
}

.product-title {
  font-size: 22px;
  margin-bottom: 6px;
  color: var(--color-text-brown);
}

.product-desc {
  font-size: 14px;
  color: var(--color-text-light);
  line-height: 1.4;
  margin-bottom: 16px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.product-action {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.product-price {
  font-size: 18px;
  font-family: var(--font-heading);
  font-weight: 700;
  color: var(--color-text-brown);
}

.add-btn {
  background-color: var(--color-primary);
  color: var(--color-white);
  padding: 8px 24px;
  border-radius: var(--border-radius-pill);
  font-weight: 600;
  font-size: 15px;
  transition: background-color 0.2s ease;
}

.add-btn:active {
  background-color: var(--color-primary-dark);
}

.loading-state,
.empty-state {
  text-align: center;
  padding: 40px 0;
  color: var(--color-text-light);
}

/* Floating Action Button (FAB) */
.fab-cart {
  position: fixed;
  bottom: 80px; /* Thấp hơn BottomNav xíu nếu có */
  right: 20px;
  background-color: var(--color-primary);
  color: white;
  width: 60px;
  height: 60px;
  border-radius: 30px;
  display: flex;
  justify-content: center;
  align-items: center;
  box-shadow: 0 4px 16px rgba(110, 68, 35, 0.4);
  z-index: 99;
  transition: transform 0.2s;
}

.fab-cart:active {
  transform: scale(0.9);
}

.fab-icon {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
}

.fab-badge {
  position: absolute;
  top: -8px;
  right: -12px;
  background-color: #DFA974; /* Màu badge sáng đẹp */
  color: var(--color-primary-dark);
  font-size: 13px;
  font-weight: 700;
  width: 24px;
  height: 24px;
  border-radius: 12px;
  display: flex;
  justify-content: center;
  align-items: center;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  border: 2px solid var(--color-bg);
}

/* Toast Notification */
.toast-notification {
  position: fixed;
  top: 24px;
  left: 50%;
  transform: translateX(-50%);
  background-color: var(--color-primary);
  color: white;
  padding: 12px 24px;
  border-radius: 30px;
  display: flex;
  align-items: center;
  gap: 10px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.2);
  z-index: 3000;
  font-weight: 500;
  font-size: 14px;
}

.toast-enter-active,
.toast-leave-active {
  transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translate(-50%, -20px);
}
</style>
