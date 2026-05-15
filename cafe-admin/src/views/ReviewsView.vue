<template>
  <div class="reviews-page fade-in">
    <!-- Thống kê -->
    <div class="stats-row" v-if="stats">
      <div class="stat-card">
        <div class="big-star-group">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="#D4A373" stroke="#D4A373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
          <span class="big-star-value">{{ stats.trung_binh || 0 }}</span>
        </div>
        <span class="text-muted">{{ stats.tong_danh_gia }} đánh giá</span>
      </div>
      <div class="star-bars">
        <div v-for="s in 5" :key="s" class="star-bar">
          <span class="star-label">
            {{ 6-s }}
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left:2px"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
          </span>
          <div class="bar-bg"><div class="bar-fill" :style="{width: barWidth(6-s)+'%'}"></div></div>
          <span class="bar-count">{{ stats.phan_bo?.[(6-s)+'_sao'] || 0 }}</span>
        </div>
      </div>
    </div>
    <!-- Filter -->
    <div class="toolbar">
      <div class="toolbar-actions">
        <select v-model="filterStar" class="filter-select" @change="load"><option value="">Tất cả sao</option><option v-for="s in 5" :key="s" :value="s">{{ s }} sao</option></select>
        <input type="date" v-model="tuNgay" class="search-input" @change="load" />
        <input type="date" v-model="denNgay" class="search-input" @change="load" />
      </div>
    </div>
    <!-- List -->
    <table class="data-table">
      <thead><tr><th>Thời gian</th><th>Mã đơn</th><th>Số sao</th><th>Bình luận</th></tr></thead>
      <tbody><tr v-for="d in items" :key="d.ma_danh_gia">
        <td>{{ d.thoi_gian }}</td>
        <td style="color:var(--text-primary)">{{ d.ma_don_hang?.slice(-8) }}</td>
        <td>
          <div class="stars-row">
            <svg v-for="s in 5" :key="s" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" :fill="s <= d.so_sao ? '#D4A373' : 'none'" :stroke="s <= d.so_sao ? '#D4A373' : '#cbd5e1'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
          </div>
        </td>
        <td style="color:var(--text-primary);max-width:400px">{{ d.binh_luan || '—' }}</td>
      </tr></tbody>
    </table>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
const items = ref([]), stats = ref(null), filterStar = ref(''), tuNgay = ref(''), denNgay = ref('')
function barWidth(star) { if(!stats.value||!stats.value.tong_danh_gia) return 0; return ((stats.value.phan_bo?.[star+'_sao']||0)/stats.value.tong_danh_gia)*100 }
async function load() { try { const r = await api.getDanhGia({ so_sao: filterStar.value, tu_ngay: tuNgay.value, den_ngay: denNgay.value }); items.value = r.data.data.danh_gias; stats.value = r.data.data.thong_ke } catch(e){ console.error(e) } }
onMounted(load)
</script>
<style scoped>
.stats-row { display:flex;gap:24px;margin-bottom:24px;align-items:center }
.stat-card { background:var(--bg-card);border:1px solid var(--border-color);border-radius:var(--radius-lg);padding:24px 32px;display:flex;flex-direction:column;align-items:center;gap:6px }
.big-star-group { display: flex; align-items: center; gap: 8px; color: var(--accent); }
.big-star-value { font-family: 'Outfit'; font-size: 2rem; font-weight: 700; }
.star-bars { flex: 1; display: flex; flex-direction: column; gap: 6px; }
.star-bar { display: flex; align-items: center; gap: 10px; }
.star-label { font-size: 0.82rem; width: 45px; display: flex; align-items: center; justify-content: flex-end; color: var(--text-secondary); }
.bar-bg { flex: 1; height: 10px; background: var(--bg-tertiary); border-radius: 5px; overflow: hidden; }
.bar-fill { height: 100%; background: var(--accent); border-radius: 5px; transition: width 0.5s ease; }
.bar-count { width: 30px; font-size: 0.82rem; color: var(--text-muted); }
.toolbar { margin-bottom: 16px; } .toolbar-actions { display: flex; gap: 10px; }
.filter-select { width: 140px; padding: 8px 12px; } .search-input { width: 150px; padding: 8px 12px; }
.stars-row { display: flex; gap: 2px; align-items: center; }
</style>
