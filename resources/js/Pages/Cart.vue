<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios'
import { computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'
import { onMounted } from 'vue'

const props = defineProps({
  cartItems: Array
})

const totalPrice = computed(() =>
  props.cartItems.reduce(
    (sum, item) => sum + item.price * item.quantity,
    0
  )
)

const page = usePage()

// 1 SP thì mới được checkout, nếu ko sẽ báo lỗi
const goCheckout = () => {
  // Giỏ trống
  if (!props.cartItems.length) {
    alert('Giỏ hàng đang trống. Vui lòng thêm sản phẩm trước khi thanh toán.')
    return
  }

  // Có sản phẩm hết hàng
  const outOfStockItem = props.cartItems.find(
    item => (item.product?.quantity ?? 0) <= 0
  )

  if (outOfStockItem) {
    alert(`Sản phẩm "${outOfStockItem.product.name}" hiện đã hết hàng. Vui lòng xóa khỏi giỏ trước khi thanh toán.`)
    return
  }

  // Có sản phẩm vượt quá tồn kho (double safety)
  const overStockItem = props.cartItems.find(
    item => item.quantity > (item.product?.quantity ?? 0)
  )

  if (overStockItem) {
    alert(`Sản phẩm "${overStockItem.product.name}" chỉ còn ${overStockItem.product.quantity} sản phẩm.`)
    return
  }

  router.visit(route('checkout'))
}

const hasOutOfStock = computed(() =>
  props.cartItems.some(item => (item.product?.quantity ?? 0) <= 0)
)

// xóa SP
const removeItem = async (itemId) => {
  if (!confirm('Xóa sản phẩm này khỏi giỏ hàng?')) return

  await axios.delete(route('cart.remove', itemId))

  // reload lại page Inertia
  window.dispatchEvent(new Event('cart-updated'))
  location.reload()
}

// cập nhật SP
const updateCart = async () => {
  for (const item of props.cartItems) {
    await axios.post(route('cart.update'), {
      cart_item_id: item.id,
      quantity: item.quantity
    })
  }

  window.dispatchEvent(new Event('cart-updated'))
  alert('Đã cập nhật giỏ hàng')
}

// phí ship
const SHIPPING_FEE = 30000

const subTotal = computed(() =>
  props.cartItems.reduce(
    (sum, item) => sum + item.price * item.quantity,
    0
  )
)

const total = computed(() => subTotal.value + SHIPPING_FEE)

onMounted(() => {
  if (page.props.flash?.success) {
    alert(page.props.flash.success)
  }

  if (page.props.flash?.error) {
    alert(page.props.flash.error)
  }
})

// check box số lượng SP
watch(
  () => props.cartItems,
  (items) => {
    items.forEach(item => {
      const max = item.product?.quantity ?? 0

      if (item.quantity < 1) {
        item.quantity = 1
      }

      if (item.quantity > max) {
        item.quantity = max
      }
    })
  },
  { deep: true }
)

</script>

<template>
  <MainLayout>
    <main class="main">
      <!--=============== BREADCRUMB ===============-->
      <section class="breadcrumb">
        <ul class="breadcrumb__list flex container">
          <li><a :href="route('home')" class="breadcrumb__link">Trang chủ</a></li>
          <li><span class="breadcrumb__link"></span>></li>
          <li><a :href="route('shop')" class="breadcrumb__link">Cửa hàng</a></li>
          <li><span class="breadcrumb__link"></span>></li>
          <li><span class="breadcrumb__link">Giỏ hàng</span></li>
        </ul>
      </section>

      <!--=============== CART ===============-->
      <section class="cart section--lg container">
        <div class="table__container">
          <table class="table">
            <thead>
              <tr>
                <th>Hình ảnh</th>
                <th>Tên sản phẩm</th>
                <th>Giá</th>
                <th>Số lượng</th>
                <th>Tạm tính</th>
                <th>Xóa</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in cartItems" :key="item.id">
                <td class="table__img-cell">
                  <div class="table__img-wrapper">
                    <img :src="item.product.primary_image?.image_url ?? '/assets/img/default.jpg'" class="table__img"
                      @click="router.visit(route('detail', item.product.id))" />
                  </div>
                </td>

                <td>
                  <h3 class="table__title">
                    {{ item.product.name }}
                  </h3>
                </td>

                <td>
                  <span class="table__price">
                    {{ Number(item.price).toLocaleString('vi-VN') }}₫
                  </span>
                </td>

                <td>
                  <template v-if="item.product.quantity > 0">
                    <input type="number" class="quantity" v-model.number="item.quantity" :min="1"
                      :max="item.product.quantity" />
                  </template>

                  <template v-else>
                    <span class="text-danger">Hết hàng</span>
                  </template>
                </td>

                <td>
                  <span class="subtotal">
                    {{ (item.price * item.quantity).toLocaleString('vi-VN') }}₫
                  </span>
                </td>

                <td>
                  <i class="fi fi-rs-trash table__trash" @click="removeItem(item.id)"></i>
                </td>
              </tr>

              <tr v-if="!cartItems.length">
                <td colspan="6" class="text-center">
                  Giỏ hàng trống
                </td>
              </tr>
            </tbody>

          </table>
        </div>

        <div class="cart__actions">
          <a href="#" class="btn flex btn__md" @click.prevent="updateCart">
            <i class="fi-rs-shuffle"></i> Cập nhật giỏ hàng
          </a>

          <a :href="route('shop')" class="btn flex btn__md">
            <i class="fi-rs-shopping-bag"></i> Tiếp tục mua sắm
          </a>
        </div>

        <div class="divider">
          <i class="fi fi-rs-fingerprint"></i>
        </div>

        <div class="cart__group grid">
          <!-- LEFT -->
          <div class="cart__note">
            <h3 class="section__title">Thông tin đơn hàng</h3>

            <ul class="cart__info-list">
              <li>
                <i class="fi fi-rs-check"></i>
                Kiểm tra lại số lượng và giá sản phẩm
              </li>
              <li>
                <i class="fi fi-rs-check"></i>
                Phí vận chuyển sẽ được tính ở bước thanh toán
              </li>
              <li>
                <i class="fi fi-rs-check"></i>
                Thông tin giao hàng (địa chỉ, SĐT) nhập tại trang thanh toán
              </li>
            </ul>

            <p class="cart__note-text">
              Nhấn <strong>“Tiến hành thanh toán”</strong> để tiếp tục.
            </p>
          </div>

          <!-- RIGHT -->
          <div class="cart__total">
            <h3 class="section__title">Tổng giỏ hàng</h3>

            <table class="cart__total-table">
              <tr>
                <td><span class="cart__total-title">Tạm tính</span></td>
                <td>
                  <span class="cart__total-price">
                    {{ subTotal.toLocaleString('vi-VN') }}₫
                  </span>
                </td>
              </tr>

              <tr>
                <td><span class="cart__total-title">Vận chuyển</span></td>
                <td>
                  <span class="cart__total-price">
                    {{ SHIPPING_FEE.toLocaleString('vi-VN') }}₫
                  </span>
                </td>
              </tr>

              <tr class="cart__total-final">
                <td><span class="cart__total-title">Tổng cộng</span></td>
                <td>
                  <span class="cart__total-price">
                    {{ total.toLocaleString('vi-VN') }}₫
                  </span>
                </td>
              </tr>
            </table>

            <a href="#" class="btn flex btn--md cart__checkout-btn" :class="{ 'disabled-btn': hasOutOfStock }"
              @click.prevent="goCheckout">
              <i class="fi fi-rs-box-alt"></i> Tiến hành thanh toán
            </a>
          </div>
        </div>
      </section>
    </main>
  </MainLayout>
</template>
