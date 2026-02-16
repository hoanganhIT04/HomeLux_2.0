<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
    order: Object
})

/* ================= UTILS ================= */
const formatPrice = (value) => {
    if (!value) return '0₫'
    return Number(value).toLocaleString('vi-VN') + '₫'
}

const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleString('vi-VN', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit'
    })
}

/* Tính tổng tiền hàng từ danh sách items */
const subTotal = computed(() => {
    // Dùng reduce để cộng dồn: Tổng += (Giá item * Số lượng)
    return props.order.items.reduce((acc, item) => {
        // Lưu ý: item.price là giá lưu tại thời điểm đặt (trong bảng order_items)
        // Nếu bạn muốn lấy giá hiện tại của sản phẩm thì đổi thành item.product.price (nhưng không khuyến khích vì sai lịch sử giá)
        return acc + (Number(item.price) * Number(item.quantity));
    }, 0);
})

/* ================= STATUS LOGIC ================= */
// Định nghĩa các bước trạng thái
const steps = [
    { key: 'processing', label: 'Đang xử lý', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' },
    { key: 'delivering', label: 'Đang giao', icon: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4' },
    { key: 'completed', label: 'Đã giao', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' }
]

const normalizedStatus = computed(() => {
    const raw = props.order.status

    if (raw === 'pending' || raw === 'paid') return 'processing'
    if (raw === 'processing') return 'processing'
    if (raw === 'delivering') return 'delivering'
    if (raw === 'completed') return 'completed'
    if (raw === 'cancelled') return 'cancelled'

    return 'processing' // fallback an toàn
})


// Tìm index của trạng thái hiện tại
const currentStepIndex = computed(() => {
    if (normalizedStatus.value === 'cancelled') return -1
    return steps.findIndex(step => step.key === normalizedStatus.value)
})

// Logic hiển thị nút hủy (chỉ khi đang xử lý)
const canCancel = computed(() => {
    return normalizedStatus.value === 'processing'
})

const cancelling = ref(false)

const cancelOrder = () => {
    if (!confirm('Bạn có chắc muốn huỷ đơn hàng này không?')) return

    cancelling.value = true

    router.post(route('orders.cancel', props.order.id), {}, {
        preserveScroll: true,
    })
}


const getStatusColor = (status) => {
    switch (status) {
        case 'cancelled': return 'text-red-600 bg-red-50 border-red-200'
        case 'completed': return 'text-green-600 bg-green-50 border-green-200'
        default: return 'text-blue-600 bg-blue-50 border-blue-200'
    }
}
</script>

<template>
    <MainLayout>
        <div class="container py-8 max-w-5xl mx-auto px-4">

            <div class="bg-white shadow-sm border border-gray-100 rounded-xl p-6 mb-6">
                <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-2xl font-bold text-gray-800">
                                Đơn hàng #{{ order.public_id || order.id }}
                            </h2>
                            <span class="px-3 py-1 text-sm font-medium rounded-full border"
                                :class="getStatusColor(normalizedStatus)">
                                {{
                                    normalizedStatus === 'processing' ? 'Đang xử lý' :
                                        normalizedStatus === 'delivering' ? 'Đang giao hàng' :
                                            normalizedStatus === 'completed' ? 'Giao hàng thành công' :
                                                'Đã huỷ'
                                }}
                            </span>

                        </div>
                        <p class="text-gray-500 text-sm mt-1">
                            Đặt ngày: {{ formatDate(order.created_at) }}
                        </p>
                    </div>

                    <div v-if="canCancel">
                        <button @click="cancelOrder" :disabled="cancelling"
                            class="px-6 py-2.5 font-semibold rounded-lg transition-all duration-200 flex items-center gap-2"
                            :class="cancelling
                                ? 'bg-gray-200 text-gray-400 cursor-not-allowed'
                                : 'bg-red-50 text-red-600 border border-red-200 hover:bg-red-600 hover:text-white'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>

                            {{ cancelling ? 'Đang huỷ...' : 'Huỷ đơn hàng' }}
                        </button>
                    </div>

                </div>
            </div>

            <div v-if="order.status !== 'cancelled'"
                class="bg-white shadow-sm border border-gray-100 rounded-xl p-8 mb-6">
                <div class="relative">
                    <div class="absolute top-1/2 left-0 w-full h-1 bg-gray-200 -translate-y-1/2 rounded-full z-0"></div>

                    <div class="absolute top-1/2 left-0 h-1 bg-[#3f9588] -translate-y-1/2 rounded-full z-0 transition-all duration-700 ease-out"
                        :style="{ width: (currentStepIndex / (steps.length - 1)) * 100 + '%' }">
                    </div>

                    <div class="relative z-10 flex justify-between w-full">
                        <div v-for="(step, index) in steps" :key="step.key" class="flex flex-col items-center group">
                            <div class="w-12 h-12 flex items-center justify-center rounded-full border-2 bg-white transition-all duration-300"
                                :class="index <= currentStepIndex
                                    ? 'border-[#3f9588] text-[#3f9588] shadow-md scale-110'
                                    : 'border-gray-300 text-gray-400'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        :d="step.icon" />
                                </svg>
                            </div>
                            <div class="mt-3 text-sm font-semibold transition-colors duration-300"
                                :class="index <= currentStepIndex ? 'text-[#3f9588]' : 'text-gray-400'">
                                {{ step.label }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="lg:col-span-2 bg-white shadow-sm border border-gray-100 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Sản phẩm đã đặt</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left border border-gray-200 rounded-lg overflow-hidden">
                            <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider">
                                <tr>
                                    <th class="px-4 py-3">Sản phẩm</th>
                                    <th class="px-4 py-3 text-center">Đơn giá</th>
                                    <th class="px-4 py-3 text-center">Số lượng</th>
                                    <th class="px-4 py-3 text-right">Thành tiền</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="item in order.items" :key="item.id" class="hover:bg-gray-50 transition">

                                    <!-- Cột sản phẩm -->
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="w-16 h-16 flex-shrink-0 border rounded-lg overflow-hidden bg-gray-50">
                                                <img v-if="item.product"
                                                    :src="item.product?.primary_image?.image_url || 'https://via.placeholder.com/150?text=No+Image'"
                                                    :alt="item.product?.name || 'Product image'"
                                                    class="w-full h-full object-cover" @error="onImageError" />
                                                <div v-else
                                                    class="w-full h-full flex items-center justify-center text-gray-400 text-xs">
                                                    No Img
                                                </div>
                                            </div>

                                            <div>
                                                <p class="font-semibold text-gray-800 line-clamp-2">
                                                    {{ item.product ? item.product.name : 'Sản phẩm đã bị xóa' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Đơn giá -->
                                    <td class="px-4 py-4 text-center text-gray-600">
                                        {{ formatPrice(item.price) }}
                                    </td>

                                    <!-- Số lượng -->
                                    <td class="px-4 py-4 text-center">
                                        <span class="bg-gray-100 px-3 py-1 rounded-md text-gray-700">
                                            x{{ item.quantity }}
                                        </span>
                                    </td>

                                    <!-- Thành tiền -->
                                    <td class="px-4 py-4 text-right font-semibold text-[#3f9588]">
                                        {{ formatPrice(item.price * item.quantity) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white shadow-sm border border-gray-100 rounded-xl p-6">
                        <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Thông tin nhận hàng</h3>
                        <div class="space-y-3 text-sm">
                            <div>
                                <p class="text-gray-500 text-xs uppercase font-bold tracking-wider">Người nhận</p>
                                <p class="font-medium text-gray-900">{{ order.receiver_name }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs uppercase font-bold tracking-wider">Điện thoại</p>
                                <p class="font-medium text-gray-900">{{ order.receiver_phone }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs uppercase font-bold tracking-wider">Địa chỉ</p>
                                <p class="font-medium text-gray-900 leading-relaxed">{{ order.full_address }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm border border-gray-100 rounded-xl p-6">
                        <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Tổng thanh toán</h3>
                        <div class="space-y-2 text-sm text-gray-600">
                            <div class="flex justify-between">
                                <span>Tạm tính</span>
                                <span>{{ formatPrice(subTotal) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Phí vận chuyển</span>
                                <span>{{ formatPrice(order.shipping_fee) }}</span>
                            </div>
                        </div>
                        <div class="border-t mt-4 pt-4 flex justify-between items-center">
                            <span class="font-bold text-gray-800">Tổng cộng</span>
                            <span class="text-2xl font-bold text-[#3f9588]">
                                {{ formatPrice(order.total_price) }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </MainLayout>
</template>