<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, router, usePage, useForm } from '@inertiajs/vue3'
import { onMounted, ref, computed } from 'vue';

/* =========================
   TAB CONTROL
========================= */
const activeTab = ref('dashboard');

const changeTab = (tabName) => {
  activeTab.value = tabName;
};

/* =========================
   USER INFO
========================= */
const page = usePage()
const user = computed(() => page.props.auth?.user)

/* =========================
   UPDATE PROFILE
========================= */
const form = useForm({
  name: user.value?.name || '',
  email: user.value?.email || '',
})

const updateProfile = () => {
  form.put(route('profile.update'), {
    preserveScroll: true,
    onSuccess: () => {
      alert('Cập nhật tên người dùng thành công!')
    }
  })
}

/* =========================
   CHANGE PASSWORD
========================= */
const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const updatePassword = () => {
  passwordForm.put(route('password.update'), {
    preserveScroll: true,
    onSuccess: () => {
      passwordForm.reset()
      alert('Đổi mật khẩu thành công!')
    }
  })
}

const showPassword = ref({
  current: false,
  new: false,
  confirm: false,
})

/* =========================
   Đơn Hàng
========================= */
const props = defineProps({
  orders: {
    type: Array,
    default: () => []
  }
})

const formatPrice = (value) => {
  if (!value) return '0₫'
  return Number(value).toLocaleString('vi-VN') + '₫'
}

const normalizeStatus = (status) => {
  if (status === 'pending' || status === 'paid') {
    return 'processing'
  }

  if (status === 'shipping') return 'delivering'
  if (status === 'delivered') return 'completed'

  return status
}

const statusMap = {
  processing: 'Đang xử lý',
  delivering: 'Đang giao hàng',
  completed: 'Đã giao hàng',
  cancelled: 'Đã huỷ'
}

const statusClass = (status) => {
  const s = normalizeStatus(status)

  switch (s) {
    case 'processing':
      return 'text-yellow-600 font-semibold'
    case 'delivering':
      return 'text-blue-600 font-semibold'
    case 'completed':
      return 'text-green-600 font-semibold'
    case 'cancelled':
      return 'text-red-600 font-semibold'
    default:
      return 'text-gray-500'
  }
}



const formatDate = (date) => {
  return new Date(date).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}



/* =========================
   LOGOUT
========================= */
const showLogoutModal = ref(false)

const logout = () => {
  router.post(route('logout'), {}, {
    onFinish: () => {
      router.visit(route('login'))
    }
  })
}

/* =========================
   ADDRESS (1 USER - 1 ADDRESS)
========================= */

const addressForm = useForm({
  receiver_name: '',
  receiver_phone: '',
  province: '',
  district: '',
  ward: '',
  detail: '',
  full_address: ''
})

const provinces = ref([])
const districts = ref([])
const wards = ref([])

const fetchProvinces = async () => {
  const res = await fetch('https://provinces.open-api.vn/api/p/')
  provinces.value = await res.json()
}

const handleProvinceChange = async (keepValue = false) => {
  if (!keepValue) {
    addressForm.district = ''
    addressForm.ward = ''
  }

  districts.value = []
  wards.value = []

  if (!addressForm.province) return

  const res = await fetch(
    `https://provinces.open-api.vn/api/p/${addressForm.province}?depth=2`
  )
  const data = await res.json()
  districts.value = data.districts
}

const handleDistrictChange = async (keepValue = false) => {
  if (!keepValue) {
    addressForm.ward = ''
  }

  wards.value = []

  if (!addressForm.district) return

  const res = await fetch(
    `https://provinces.open-api.vn/api/d/${addressForm.district}?depth=2`
  )
  const data = await res.json()
  wards.value = data.wards
}

const fullAddress = computed(() => {
  const province = provinces.value.find(p => p.code == addressForm.province)
  const district = districts.value.find(d => d.code == addressForm.district)
  const ward = wards.value.find(w => w.code == addressForm.ward)

  return [
    addressForm.detail,
    ward?.name,
    district?.name,
    province?.name
  ].filter(Boolean).join(', ')
})

onMounted(async () => {
  await fetchProvinces()

  const res = await fetch(route('addresses.index'))
  const data = await res.json()

  if (data) {
    Object.assign(addressForm, data)

    await handleProvinceChange(true)
    await handleDistrictChange(true)
  }
})

const saveAddress = () => {
  addressForm.full_address = fullAddress.value

  addressForm.post(route('addresses.store'), {
    preserveScroll: true,
    onSuccess: () => {
      alert('Lưu địa chỉ thành công!')
      // KHÔNG reset form nữa
    }
  })
}

</script>


<template>

  <MainLayout>
    <main class="main">
      <!--=============== BREADCRUMB ===============-->
      <section class="breadcrumb">
        <ul class="breadcrumb__list flex container">
          <li><a :href="route('home')" class="breadcrumb__link">Trang chủ</a></li>
          <li><span class="breadcrumb__link">></span></li>
          <li><a :href="route('account')" class="breadcrumb__link">Tài khoản</a></li>
        </ul>
      </section>

      <!--=============== ACCOUNTS ===============-->
      <section class="accounts section--lg">
        <div class="accounts__container container grid">
          <div class="account__tabs">
            <p class="account__tab" :class="{ 'active-tab': activeTab === 'dashboard' }"
              @click="changeTab('dashboard')">
              <i class="fi fi-rs-settings-sliders"></i> Bảng điều khiển
            </p>

            <p class="account__tab" :class="{ 'active-tab': activeTab === 'orders' }" @click="changeTab('orders')">
              <i class="fi fi-rs-shopping-bag"></i> Đơn hàng
            </p>

            <!-- <p class="account__tab" :class="{ 'active-tab': activeTab === 'update-profile' }"
              @click="changeTab('update-profile')">
              <i class="fi fi-rs-user"></i> Cập nhật hồ sơ
            </p> -->

            <p class="account__tab" :class="{ 'active-tab': activeTab === 'address' }" @click="changeTab('address')">
              <i class="fi fi-rs-marker"></i> Địa chỉ của tôi
            </p>

            <!-- <p class="account__tab" :class="{ 'active-tab': activeTab === 'change-password' }"
              @click="changeTab('change-password')">
              <i class="fi fi-rs-settings-sliders"></i> Đổi mật khẩu
            </p> -->

            <!-- <p class="account__tab" @click="showLogoutModal = true">
              <i class="fi fi-rs-exit"></i> Đăng xuất
            </p> -->

          </div>
          <div class="tabs__content">
            <div class="tab__content" :class="{ 'active-tab': activeTab === 'dashboard' }"
              v-if="activeTab === 'dashboard'">
              <h3 class="tab__header">Xin chào, {{ user?.name }}</h3>
              <div class="tab__body">
                <p class="tab__description">
                  Từ bảng điều khiển tài khoản của bạn, bạn có thể dễ dàng kiểm tra & xem các đơn hàng gần đây, quản lý
                  địa chỉ giao hàng và thanh toán cũng như chỉnh sửa mật khẩu và thông tin tài khoản của bạn
                </p>
              </div>
            </div>

            <div class="tab__content" :class="{ 'active-tab': activeTab === 'orders' }" v-if="activeTab === 'orders'">
              <h3 class="tab__header">Đơn hàng của bạn</h3>
              <div class="tab__body">
                <div class="max-h-[300px] overflow-y-auto">
                  <table class="placed__order-table w-full table-fixed border-collapse">

                    <thead>
                      <tr>
                        <th class="sticky top-0 bg-white z-20 text-left">Đơn hàng</th>
                        <th class="sticky top-0 bg-white z-20 text-left">Ngày</th>
                        <th class="sticky top-0 bg-white z-20 text-left">Trạng thái</th>
                        <th class="sticky top-0 bg-white z-20 text-left">Tổng tiền</th>
                        <th class="sticky top-0 bg-white z-20 text-left">Hành động</th>
                      </tr>
                    </thead>

                    <tbody>
                      <tr v-for="order in orders ?? []" :key="order.id">
                        <td>#{{ order.public_id }}</td>

                        <td>
                          {{ formatDate(order.created_at) }}
                        </td>

                        <td>
                          <span :class="statusClass(order.status)">
                            {{ statusMap[normalizeStatus(order.status)] }}
                          </span>
                        </td>

                        <td>
                          {{ formatPrice(order.total_price) }}
                        </td>

                        <td>
                          <a href="#" @click.prevent="router.visit(route('orders.show', order.id))"
                            class="text-[#3f9588] hover:underline">
                            Xem
                          </a>
                        </td>
                      </tr>

                      <tr v-if="!orders || !orders.length">
                        <td colspan="5" class="text-center">
                          Bạn chưa có đơn hàng nào.
                        </td>
                      </tr>
                    </tbody>

                  </table>
                </div>
              </div>

            </div>

            <div class="tab__content" :class="{ 'active-tab': activeTab === 'update-profile' }"
              v-if="activeTab === 'update-profile'">
              <h3 class="tab__header">Cập nhật hồ sơ</h3>
              <div class="tab__body">
                <form class="form grid" @submit.prevent="updateProfile">
                  <input type="text" placeholder="Tên người dùng" class="form__input" v-model="form.name" />
                  <input type="email" class="form__input" placeholder="Email" v-model="form.email" disabled />
                  <div class="form__btn">
                    <button type="submit" class="btn btn--md" :disabled="form.processing">Lưu</button>
                  </div>
                </form>
              </div>
            </div>

            <div class="tab__content" :class="{ 'active-tab': activeTab === 'address' }" v-if="activeTab === 'address'">
              <h3 class="tab__header">Địa chỉ giao hàng</h3>
              <div class="tab__body">
                <div class="tab__body address__form">
                  <!-- Tên người nhận -->
                  <div class="form__group">
                    <label>Họ và tên người nhận</label>
                    <input type="text" v-model="addressForm.receiver_name" placeholder="Nhập họ tên" required />
                    <p v-if="addressForm.errors.receiver_name" class="text-red-500 text-sm">
                      {{ addressForm.errors.receiver_name }}
                    </p>
                  </div>

                  <!-- SĐT -->
                  <div class="form__group">
                    <label>Số điện thoại</label>
                    <input type="tel" v-model="addressForm.receiver_phone" placeholder="Nhập số điện thoại"
                      pattern="(03|05|07|08|09)[0-9]{8}" required />
                    <p v-if="addressForm.errors.receiver_phone" class="text-red-500 text-sm">
                      {{ addressForm.errors.receiver_phone }}
                    </p>
                  </div>

                  <!-- Tỉnh -->
                  <div class="form__group">
                    <label>Tỉnh / Thành phố</label>
                    <select v-model="addressForm.province" @change="handleProvinceChange" required>
                      <option disabled value="">Chọn tỉnh/thành</option>
                      <option v-for="province in provinces" :key="province.code" :value="province.code">
                        {{ province.name }}
                      </option>
                    </select>
                    <p v-if="addressForm.errors.province" class="text-red-500 text-sm">
                      {{ addressForm.errors.province }}
                    </p>
                  </div>

                  <!-- Quận -->
                  <div class="form__group">
                    <label>Quận / Huyện</label>
                    <select v-model="addressForm.district" @change="handleDistrictChange" :disabled="!districts.length"
                      required>
                      <option disabled value="">Chọn quận/huyện</option>
                      <option v-for="district in districts" :key="district.code" :value="district.code">
                        {{ district.name }}
                      </option>
                    </select>
                    <p v-if="addressForm.errors.district" class="text-red-500 text-sm">
                      {{ addressForm.errors.district }}
                    </p>
                  </div>

                  <!-- Phường -->
                  <div class="form__group">
                    <label>Phường / Xã</label>
                    <select v-model="addressForm.ward" :disabled="!wards.length" required>
                      <option disabled value="">Chọn phường/xã</option>
                      <option v-for="ward in wards" :key="ward.code" :value="ward.code">
                        {{ ward.name }}
                      </option>
                    </select>
                    <p v-if="addressForm.errors.ward" class="text-red-500 text-sm">
                      {{ addressForm.errors.ward }}
                    </p>
                  </div>

                  <!-- Địa chỉ chi tiết -->
                  <div class="form__group">
                    <label>Địa chỉ chi tiết</label>
                    <input type="text" v-model="addressForm.detail" placeholder="Nhập số nhà, tên đường..." required />
                    <p v-if="addressForm.errors.detail" class="text-red-500 text-sm">
                      {{ addressForm.errors.detail }}
                    </p>
                  </div>

                  <!-- Địa chỉ đầy đủ (auto) -->
                  <div class="form__group full-width">
                    <label>Địa chỉ đầy đủ</label>
                    <input type="text" :value="fullAddress" disabled class="address__preview" />
                  </div>

                  <div class="form__action full-width">
                    <button type="button" class="address__btn" @click="saveAddress" :disabled="addressForm.processing">
                      {{ addressForm.processing ? 'Đang lưu...' : 'Lưu địa chỉ' }}
                    </button>
                  </div>

                </div>
              </div>
            </div>

            <div class="tab__content" :class="{ 'active-tab': activeTab === 'change-password' }"
              v-if="activeTab === 'change-password'">
              <h3 class="tab__header">Đổi mật khẩu</h3>
              <div class="tab__body">
                <form class="form grid" @submit.prevent="updatePassword">
                  <input type="password" placeholder="Mật khẩu hiện tại" class="form__input"
                    v-model="passwordForm.current_password" />
                  <p v-if="passwordForm.errors.current_password" class="text-red-500 text-sm">
                    {{ passwordForm.errors.current_password }}
                  </p>

                  <input type="password" placeholder="Mật khẩu mới" class="form__input"
                    v-model="passwordForm.password" />
                  <p v-if="passwordForm.errors.password" class="text-red-500 text-sm">
                    {{ passwordForm.errors.password }}
                  </p>

                  <input type="password" placeholder="Xác nhận mật khẩu" class="form__input"
                    v-model="passwordForm.password_confirmation" />
                  <p v-if="passwordForm.errors.password_confirmation" class="text-red-500 text-sm">
                    {{ passwordForm.errors.password_confirmation }}
                  </p>

                  <p v-if="passwordForm.errors.general" class="text-red-500 text-sm mb-2">
                    {{ passwordForm.errors.general }}
                  </p>

                  <div class="form__btn">
                    <button type="submit" class="btn btn--md" :disabled="passwordForm.processing">
                      Lưu
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
  </MainLayout>
</template>
