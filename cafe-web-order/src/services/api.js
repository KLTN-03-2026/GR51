import axios from 'axios'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1'

const apiClient = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

export default {
  // Lấy menu đang kinh doanh
  getMenu() {
    return apiClient.get('/menu')
  },
  
  // Lấy thông tin bàn
  getTableInfo(maBan) {
    return apiClient.get(`/tables/${maBan}`)
  },
  
  // Submit đơn hàng QR
  submitQrOrder(payload) {
    return apiClient.post('/don-hang/qr', payload)
  },

  // Lấy trạng thái đơn hàng (Polling)
  getOrderStatus(maDonHang) {
    return apiClient.get(`/don-hang/qr/${maDonHang}`)
  },

  // Gửi đánh giá
  submitReview(payload) {
    return apiClient.post('/danh-gia/qr', payload)
  },

  // Huỷ đơn hàng (khách hàng)
  cancelOrderQr(maDonHang, lyDo = 'Khách hàng tự hủy') {
    return apiClient.put(`/don-hang/qr/${maDonHang}/huy`, { ly_do_huy: lyDo })
  }
}
