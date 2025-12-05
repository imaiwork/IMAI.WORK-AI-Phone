<template>
    <div class="pt-[10px]">
        <el-form label-width="120px" ref="formRef" :model="formData">
            <el-form-item label="AI接口">
                <div>
                    <ElRadioGroup v-model="currentKey" @change="modelSelectChange($event)">
                        <el-radio v-for="(model, key) in modelValue" :key="key" :label="key">
                            {{ model.name }}
                        </el-radio>
                    </ElRadioGroup>
                    <div v-if="isGpt" class="form-tips !text-[14px]">
                        选择此通道，需要在【key池】添加gpt3.5的key才能正常使用，否则无法训练
                        <router-link
                            v-perms="['setting.KeyPool/lists']"
                            :to="{
                                path: getRoutePath('setting.KeyPool/lists')
                            }"
                        >
                            <el-button type="primary" link>前往添加</el-button>
                        </router-link>
                    </div>
                    <div v-if="isChatGLM">
                        <span class="form-tips !text-[14px]"
                            >开通网址：https://open.bigmodel.cn/dev/api#text_embedding</span
                        >
                        <a
                            href="https://open.bigmodel.cn/dev/api#text_embedding"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <el-button type="primary" link class="ml-2">前往开通</el-button>
                        </a>
                    </div>
                    <div v-if="isXUNFEI">
                        <span class="form-tips !text-[14px]"
                            >开通网址：https://www.xfyun.cn/doc/spark/embedding_api.html</span
                        >
                        <a
                            href="https://www.xfyun.cn/doc/spark/embedding_api.html"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <el-button type="primary" link class="ml-2">前往开通</el-button>
                        </a>
                    </div>
                </div>
            </el-form-item>

            <el-form-item label="自定义api域名" v-if="isGpt">
                <div class="w-[400px]">
                    <el-input v-model="formData.agency_api" placeholder="请输入自定义域名" />
                    <div class="form-tips">{{ formData.agency_tips }}</div>
                </div>
            </el-form-item>
            <el-form-item
                label="自定义api域名"
                v-if="currentKey === 'm3e'"
                prop="agency_api"
                :rules="[{ message: '请输入自定义api域名', required: true }]"
            >
                <div class="w-[400px]">
                    <el-input v-model="formData.agency_api" placeholder="请输入自定义api域名" />
                    <div class="form-tips">{{ formData.agency_tips }}</div>
                </div>
            </el-form-item>
        </el-form>
    </div>
</template>

<script setup lang="ts">
import { getRoutePath } from '@/router'
import { useVModel } from '@vueuse/core'
import type { PropType } from 'vue'
const props = defineProps({
    modelValue: {
        type: Object as PropType<Record<string, any>>,
        required: true
    }
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: any): void
}>()

//表单ref
const formRef = shallowRef()

//表单数据
const models = useVModel(props, 'modelValue', emit)
const formData = ref<any>({})
const currentKey = ref('')
const isChatGLM = computed(() => currentKey.value.includes('zhipu'))
const isXUNFEI = computed(() => currentKey.value === 'xunfei')
const isGpt = computed(() => currentKey.value.includes('openai'))
const modelSelectChange = (key: any) => {
    Object.keys(models.value).forEach((item) => {
        models.value[item].checked = false
        if (key === item) {
            formData.value = models.value[item]
            models.value[item].checked = true
        }
    })
}

watch(
    () => props.modelValue,
    (value) => {
        currentKey.value = Object.keys(models.value).find((key) => value[key].checked) || ''
        console.log(currentKey.value)
        if (currentKey.value) {
            formData.value = models.value[currentKey.value]
        }
    },
    {
        immediate: true
    }
)
defineExpose({
    validate() {
        return formRef.value?.validate()
    }
})
</script>

<style scoped lang="scss"></style>
