<template>
    <view
        v-if="showDropdown"
        class="dropdown-mask"
        @click="showDropdown = !showDropdown"
    ></view>
    <view class="dropdown">
        <view class="flex items-center" @click="showDropdown = !showDropdown">
            <slot :show="showDropdown"></slot>
        </view>
        <view
            class="dropdown-menu"
            v-show="showDropdown"
            :class="menuClass"
            @click="showDropdown = !showDropdown"
        >
            <!-- 下拉菜单的内容 -->
            <slot name="menu"></slot>
        </view>
    </view>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'

const props = defineProps({
    mode: {
        type: String,
        default: 'down'
    } // 默认向下展开
})

const showDropdown = ref<boolean>(false)

const menuClass = computed(() => {
    return {
        'dropdown-menu-up': props.mode === 'up',
        'dropdown-menu-right': props.mode === 'right',
        'dropdown-menu-down': props.mode === 'down',
        'dropdown-menu-left': props.mode === 'left'
    }
})
</script>

<style lang="scss">
.dropdown {
    position: relative;
    flex: 1;
    width: auto;
}

.dropdown-mask {
    position: fixed;
    z-index: 3;
    right: 0;
    left: 0;
    top: 0;
    bottom: 0;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    flex: 1;
    width: auto;
    white-space: pre;
    border-radius: 16rpx;
    background-color: #fff;
    box-shadow: 0 3rpx 16rpx #00000029;
    z-index: 9999;
}

.dropdown-menu-up {
    inset: auto auto 130% 0%;
    /*transform: translateX(-50%);*/
}

.dropdown-menu-right {
    inset: auto auto 50% -130%;
    transform: translateY(-50%);
}

.dropdown-menu-down {
    inset: auto auto 0% 50%;
    transform: translateX(-50%);
}

.dropdown-menu-left {
    inset: auto auto 50% 130%;
    transform: translateY(-50%);
}
</style>
