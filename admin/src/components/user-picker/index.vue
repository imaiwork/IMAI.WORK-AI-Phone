<template>
    <div class="flex flex-col">
        <user-popup
            v-model="selectData"
            :title="title"
            :type="type"
            :maxNum="maxNum"
            :disabled="disabled"
        >
            <slot name="popup"></slot>
        </user-popup>
        <!--        <table-detail v-model="selectData" :status="status" />-->
    </div>
</template>

<script lang="ts" setup>
import { withDefaults, computed } from 'vue'
const UserPopup = defineAsyncComponent(() => import('./popup.vue'));
// import tableDetail from './table-detail.vue'

const props = withDefaults(
    defineProps<{
        modelValue?: any
        selectData?: any
        title?: string
        type?: string
        maxNum?: number
        disabled?: boolean
    }>(),
    {
        modelValue: [],
        selectData: [],
        title: '',
        // 类型: 多选(multiple) ｜ 单选(single)
        type: 'multiple',
        // 选择最大数量
        maxNum: 10,
        disabled: false
    }
)

const emit = defineEmits(['update:modelValue', 'update:selectData'])

const selectData: any = computed({
    get: () => {
        return props.selectData || []
    },
    set: (value) => {
        console.log(value)
        if (props.type === 'single') {
            emit('update:modelValue', value.id)
            emit('update:selectData', value)
        } else {
            emit(
                'update:modelValue',
                value.map((item: Event | any) => item.id)
            )
            emit('update:selectData', value)
        }
    }
})
</script>
