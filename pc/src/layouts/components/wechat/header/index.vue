<template>
    <div class="h-[76px] shadow-lighter relative px-4 z-[999] bg-black">
        <div class="h-full items-center justify-between flex">
            <Back />
            <div class="flex-1 mx-[80px] justify-center">
                <Menu />
            </div>
            <User is-wechat />
        </div>
    </div>
</template>

<script setup lang="ts">
import Back from "./back.vue";
import Menu from "./menu.vue";
import User from "../../header/user.vue";
</script>

<style scoped lang="scss">
:deep(.btn-group) {
    @apply text-white;
}
:deep(.btn-name) {
    @apply text-white;
}
</style>
