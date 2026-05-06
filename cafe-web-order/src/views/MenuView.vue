<script setup>
import { ref, onMounted, inject, computed } from "vue";
import api from "../services/api";
import ProductModal from "../components/common/ProductModal.vue";
import CartDrawer from "../components/common/CartDrawer.vue";

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
const storeOpen = ref(true);

const toastMessage = ref('');
const showToast = ref(false);
let toastTimeout = null;

const notify = (msg) => {
  toastMessage.value = msg;
  showToast.value = true;
  if (toastTimeout) clearTimeout(toastTimeout);
  toastTimeout = setTimeout(() => { showToast.value = false; }, 2500);
};

onMounted(async () => {
  loading.value = true;
  try {
    const response = await api.getMenu();
    if (response.data && response.data.data) {
      const allItems = [];
      const cats = [];
      response.data.data.danh_mucs.forEach((dm) => {
        cats.push(dm.ten_danh_muc);
        if (dm.mons) {
          dm.mons.forEach((mon) => {
            allItems.push({ ...mon, danh_muc_name: dm.ten_danh_muc });
          });
        }
      });
      menuItems.value = allItems;
      categories.value = ["Tất cả", ...new Set(cats)];
      availableToppings.value = response.data.data.toppings || [];
      availableSizes.value = response.data.data.sizes || [];
      storeOpen.value = response.data.data.is_open !== false;
    }
  } catch (error) {
    console.error("Lỗi lấy menu:", error);
  } finally {
    loading.value = false;
  }
});

const filteredMenu = computed(() => {
  if (activeCategory.value === "Tất cả") return menuItems.value;
  return menuItems.value.filter(item => item.danh_muc_name === activeCategory.value);
});

const formatPrice = (price) => new Intl.NumberFormat("vi-VN").format(price) + 'đ';

const openModal = (item) => {
  editItemData.value = null;
  selectedItem.value = item;
  showModal.value = true;
};

const handleAddToCart = (payload, isEdit) => {
  if (isEdit) {
    const index = cart.value.findIndex(i => i.cartItemId === payload.cartItemId);
    if (index !== -1) cart.value[index] = payload;
  } else {
    const existing = cart.value.find(i => i.id === payload.id && i.ghi_chu === payload.ghi_chu);
    if (existing) existing.quantity += payload.quantity;
    else cart.value.push(payload);
  }
  notify(`Đã thêm ${payload.ten_mon}!`);
};

const updateCartQuantity = ({ item, delta }) => {
  const existing = cart.value.find(i => i.cartItemId === item.cartItemId);
  if (existing) {
    existing.quantity += delta;
    if (existing.quantity <= 0) removeCartItem(item);
  }
};

const removeCartItem = (item) => {
  const index = cart.value.findIndex(i => i.cartItemId === item.cartItemId);
  if (index !== -1) cart.value.splice(index, 1);
};

const submitOrder = (paymentMethod) => {
  isCartDrawerOpen.value = false;
  emit('submit-order', paymentMethod);
};
</script>

<template>
  <div class="menu-view animate-fade-in">
    <header class="top-header">
      <div class="logo">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/><line x1="6" y1="2" x2="6" y2="4"/><line x1="10" y1="2" x2="10" y2="4"/><line x1="14" y1="2" x2="14" y2="4"/></svg>
        <div class="brand-text">
          <h1 class="brand-name">Gunpla Coffe</h1>
          <p class="brand-slogan">Nơi hương vị thăng hoa</p>
        </div>
      </div>
      <div v-if="tableCode" class="table-badge">{{ tableName || "Bàn " + tableCode }}</div>
    </header>

    <div v-if="!storeOpen" class="closed-banner">
      <div class="banner-content"><span>Cửa hàng hiện đang tạm nghỉ.</span></div>
    </div>

    <section class="category-section">
      <div class="category-scroll">
        <button v-for="cat in categories" :key="cat" class="cat-btn" :class="{ active: activeCategory === cat }" @click="activeCategory = cat">{{ cat }}</button>
      </div>
    </section>

    <section class="product-list">
      <div v-if="loading" class="loading-state">Đang tải...</div>
      <div v-else v-for="item in filteredMenu" :key="item.id" class="product-card">
        <div class="product-img">
          <img :src="item.hinh_anh && item.hinh_anh !== 'default.jpg' ? (item.hinh_anh.startsWith('http') ? item.hinh_anh : 'http://localhost:8000/storage/' + item.hinh_anh) : 'https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&q=80&w=400&h=300'" :alt="item.ten_mon" />
        </div>
        <div class="product-info">
          <h3 class="product-title">{{ item.ten_mon }}</h3>
          <p class="product-desc">{{ item.mo_ta || "Thưởng thức hương vị cà phê nguyên chất." }}</p>
          <div class="product-action">
            <span class="product-price">{{ formatPrice(item.gia_ban) }}</span>
            <button class="add-btn" :class="{ disabled: !storeOpen || item.is_het_hang }" @click="storeOpen && !item.is_het_hang ? openModal(item) : null">
              {{ item.is_het_hang ? 'Hết hàng' : 'Thêm' }}
            </button>
          </div>
        </div>
      </div>
    </section>

    <Teleport to="body">
      <button class="fab-cart" v-if="cart.length > 0" @click="isCartDrawerOpen = true">
        <div class="fab-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          <span class="fab-badge">{{ cart.reduce((sum, item) => sum + item.quantity, 0) }}</span>
        </div>
      </button>
    </Teleport>

    <ProductModal :is-open="showModal" :item="selectedItem" :edit-item="editItemData" :toppings="availableToppings" :sizes="availableSizes" @close="showModal = false" @add-to-cart="handleAddToCart" />
    <CartDrawer :is-open="isCartDrawerOpen" :cart="cart" @close="isCartDrawerOpen = false" @update-quantity="updateCartQuantity" @remove="removeCartItem" @submit-order="submitOrder" />
  </div>
</template>

<style scoped>
.menu-view { padding-bottom: 80px; }
.top-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; background: #fff; position: sticky; top: 0; z-index: 10; }
.logo { display: flex; align-items: center; gap: 10px; }
.brand-name { font-size: 20px; margin: 0; color: var(--color-text-brown); }
.table-badge { background: var(--color-primary); color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 13px; font-weight: 600; }
.closed-banner { background: #fef2f2; color: #991b1b; padding: 10px; text-align: center; font-size: 14px; margin: 0 20px 10px; border-radius: 8px; }
.category-scroll { display: flex; overflow-x: auto; gap: 10px; padding: 10px 20px; scrollbar-width: none; }
.cat-btn { padding: 8px 16px; border-radius: 20px; background: #fff; white-space: nowrap; font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
.cat-btn.active { background: var(--color-primary); color: #fff; }
.product-list { padding: 0 20px; display: grid; gap: 16px; }
.product-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.product-img img { width: 100%; height: 160px; object-fit: cover; }
.product-info { padding: 12px; }
.product-title { font-size: 18px; margin: 0 0 4px; }
.product-desc { font-size: 13px; color: #666; margin-bottom: 12px; }
.product-action { display: flex; justify-content: space-between; align-items: center; }
.product-price { font-weight: 700; color: var(--color-text-brown); }
.add-btn { background: var(--color-primary); color: #fff; padding: 6px 16px; border-radius: 16px; font-size: 14px; font-weight: 600; }
.add-btn.disabled { background: #ccc; }
.fab-cart { position: fixed; bottom: 80px; right: 20px; width: 56px; height: 56px; background: var(--color-primary); border-radius: 28px; color: #fff; display: flex; justify-content: center; align-items: center; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
.fab-badge { position: absolute; top: -5px; right: -5px; background: #ff4d4f; width: 20px; height: 20px; border-radius: 10px; font-size: 11px; display: flex; justify-content: center; align-items: center; }
</style>
