<template>
    <div class="w-full h-full bg-[#ffffff33] rounded-full" style="backdrop-filter: blur(5px)">
        <ElPopover
            popper-class="!min-w-[212px] !p-2 !rounded-xl !border-[#efefef]"
            :popper-style="{
                backgroundColor: 'var(--color-white)',
                borderColor: 'var(--color-white)',
            }"
            :trigger="trigger"
            width="212"
            :show-arrow="false"
            :popper-options="{
                modifiers: [{ name: 'offset', options: { offset: [100, 20] } }],
            }"
            @show="visibleChange(true, data.id)"
            @hide="visibleChange(false, data.id)">
            <template #reference>
                <div
                    class="origin-center cursor-pointer w-full h-full flex items-center justify-center"
                    :class="[horizontal ? 'rotate-0' : 'rotate-90']">
                    <Icon name="el-icon-MoreFilled"></Icon>
                </div>
            </template>
            <div class="flex flex-col gap-2">
                <DefineTemplate v-slot="{ label, icon }">
                    <div
                        class="h-11 px-3 rounded-lg cursor-pointer flex items-center gap-3 hover:bg-[#F6F6F6] hover:shadow-[0_0_0_1px_rgba(239,239,239,1)]">
                        <span class="flex w-5 h-5 rounded items-center justify-center bg-[#0000000b]">
                            <Icon :name="icon" color="#000000"></Icon>
                        </span>
                        <span class="text-black">{{ label }}</span>
                    </div>
                </DefineTemplate>
                <div v-for="(menu, index) in menuList" :key="index" @click="menu.click(data)">
                    <SelectItemTemplate :label="menu.label" :icon="menu.icon" />
                </div>
            </div>
        </ElPopover>
    </div>
</template>

<script setup lang="ts">
import { HandleMenuType } from "./typings";

const props = defineProps({
    data: {
        type: Object as PropType<{ id?: string; [key: string]: any }>,
        default: () => ({}),
    },
    menuList: {
        type: Array as PropType<Array<HandleMenuType>>,
        default: () => [],
    },
    horizontal: {
        type: Boolean,
        default: false,
    },
    trigger: {
        type: String as PropType<"hover" | "click">,
        default: "hover",
    },
});

const active = ref();

const visibleChange = (visible: boolean, id: string) => {
    active.value = visible ? id : "";
};

const { DefineTemplate, UseTemplate: SelectItemTemplate } = useTemplate();
</script>
