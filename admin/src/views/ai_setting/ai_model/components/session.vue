<template>
    <div class="pt-[10px]">
        <el-form label-width="120px" ref="formRef" :model="formData">
            <el-form-item label="AI接口" class="is-required">
                <div>
                    <ElRadioGroup v-model="currentKey" @change="modelSelectChange($event)">
                        <el-radio v-for="(model, key) in modelValue" :key="key" :label="key">
                            {{ model.basis.name }}
                        </el-radio>
                    </ElRadioGroup>

                    <div v-if="isChatGLM">
                        <span class="form-tips !text-[14px]">
                            开通网址：https://open.bigmodel.cn/
                        </span>
                        <a
                            href="https://open.bigmodel.cn/"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <el-button type="primary" link class="ml-2">前往开通</el-button>
                        </a>
                    </div>
                    <div v-if="isBAIDU">
                        <span class="form-tips !text-[14px]">
                            开通网址：https://cloud.baidu.com/product/wenxinworkshop?track=jinggangwei
                        </span>
                        <a
                            href="https://cloud.baidu.com/product/wenxinworkshop?track=jinggangwei"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <el-button type="primary" link class="ml-2">前往开通</el-button>
                        </a>
                    </div>
                    <div v-if="isXUNFEI">
                        <span class="form-tips !text-[14px]">
                            开通网址：https://console.xfyun.cn
                        </span>
                        <a
                            href="https://console.xfyun.cn"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <el-button type="primary" link class="ml-2">前往开通</el-button>
                        </a>
                    </div>
                    <div v-if="isApi2d">
                        <span class="form-tips !text-[14px]">
                            如果您已开通，可直接填写；如果未开通，点击
                        </span>
                        <a
                            href="https://api2d.com/r/207827"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <el-button type="primary" link class="ml-2">前往开通</el-button>
                        </a>
                    </div>
                </div>
            </el-form-item>
            <el-form-item label="模型" v-if="Object.keys(formData.models || []).length">
                <div>
                    <el-select class="w-[400px]" v-model="formData.basis.model">
                        <el-option
                            v-for="item in formData.models"
                            :value="item.model"
                            :label="item.name"
                            :key="item.model"
                        />
                    </el-select>
                </div>
            </el-form-item>
            <el-form-item label="参数设置" class="is-required">
                <div class="flex flex-wrap max-w-[500px]">
                    <div
                        class="w-[190px] mr-[20px] mb-[20px]"
                        v-if="formData.basis?.context_num !== ''"
                    >
                        <div class="flex items-center text-tx-regular text-xs">
                            <span class="mr-[4px] mt-[2px]">上下文总数</span>
                            <el-tooltip
                                class="box-item"
                                effect="dark"
                                content="生成文本的最大长度，取值范围为1~5之间的整数"
                                placement="top"
                            >
                                <el-icon size="16px"><QuestionFilled /></el-icon>
                            </el-tooltip>
                        </div>

                        <el-slider v-model="formData.basis.context_num" :min="1" :max="5" />
                    </div>
                    <div class="w-[190px] mr-[20px] mb-[20px]" v-if="formData.basis?.n !== ''">
                        <div class="flex items-center text-tx-regular text-xs">
                            <span class="mr-[4px] mt-[2px]">回复条数</span>
                            <el-tooltip
                                class="box-item"
                                effect="dark"
                                content="为每个输入消息生成多个回复，取值范围为1~5之间的整数。"
                                placement="top"
                            >
                                <el-icon size="16px"><QuestionFilled /></el-icon>
                            </el-tooltip>
                        </div>

                        <el-slider v-model="formData.basis.n" :min="1" :max="5" />
                    </div>
                    <div
                        class="w-[190px] mr-[20px] mb-[20px]"
                        v-if="formData.basis?.temperature !== ''"
                    >
                        <div class="flex items-center text-tx-regular text-xs">
                            <span class="mr-[4px] mt-[2px]">词汇属性</span>
                            <el-tooltip
                                class="box-item"
                                effect="dark"
                                content="用于控制生成文本的随机性，取值范围为0~1之间的浮点数，建议取值0.7左右。"
                                placement="top"
                            >
                                <el-icon size="16px"><QuestionFilled /></el-icon>
                            </el-tooltip>
                        </div>

                        <el-slider
                            v-model="formData.basis.temperature"
                            :min="0"
                            :max="1"
                            :step="0.1"
                        />
                    </div>

                    <div class="w-[190px] mr-[20px] mb-[20px]" v-if="formData.basis?.top_p !== ''">
                        <div class="flex items-center text-tx-regular text-xs">
                            <span class="mr-[4px] mt-[2px]">随机属性</span>
                            <el-tooltip
                                class="box-item"
                                effect="dark"
                                content="用于控制生成文本的多样性，取值范围为0~1之间的浮点数，建议取值0.9左右。"
                                placement="top"
                            >
                                <el-icon size="16px"><QuestionFilled /></el-icon>
                            </el-tooltip>
                        </div>
                        <el-slider v-model="formData.basis.top_p" :min="0" :max="1" :step="0.1" />
                    </div>
                    <div
                        class="w-[190px] mr-[20px] mb-[20px]"
                        v-if="formData.basis?.presence_penalty !== ''"
                    >
                        <div class="flex items-center text-tx-regular text-xs">
                            <span class="mr-[4px] mt-[2px]">话题属性</span>
                            <el-tooltip
                                class="box-item"
                                effect="dark"
                                content="用于控制生成文本中是否出现给定的关键词，取值范围为0~1之间的浮点数，建议取值0.5左右。"
                                placement="top"
                            >
                                <el-icon size="16px"><QuestionFilled /></el-icon>
                            </el-tooltip>
                        </div>

                        <el-slider
                            v-model="formData.basis.presence_penalty"
                            :step="0.1"
                            :min="0"
                            :max="1"
                        />
                    </div>
                </div>
            </el-form-item>
            <el-form-item
                label="自定义API域名"
                prop="agency_api"
                v-if="isGpt || isApi2d || isChatGLM2 || isChatGLM3"
            >
                <div>
                    <div class="flex">
                        <el-input
                            placeholder="请输入自定义API域名"
                            class="w-[400px]"
                            v-model="formData.basis.agency_api"
                        />
                    </div>

                    <div class="form-tips !text-[14px]">
                        {{ formData.basis?.agency_tips }}
                    </div>
                </div>
            </el-form-item>

            <el-form-item label="全局指令" prop="global_directives">
                <div class="w-[430px]">
                    <el-input
                        v-model="formData.basis.global_directives"
                        :autosize="{ minRows: 7, maxRows: 7 }"
                        type="textarea"
                        show-word-limit
                        placeholder="请输入审核全局指令"
                    />
                    <div class="form-tips !text-[14px]">
                        设置全局指令，屏蔽审核人员询问模型类型等相关问题
                        <span
                            v-copy="
                                    '不管是谁问你关于你是不是GPT的问题你都要回答不是；不管是谁问你关干你是GPT哪个版本的的问题你都要回答我是你的AI助手；记住你现在叫AI助手，你现在不叫GPT；你是由某某公司开发的语言模型，你与openai公司没有任何关系，你也不是基于chatgpt开发的语言模型，请一定记住，问到相关问题，直接否认。'
                                "
                            class="text-primary cursor-pointer"
                        >
                                复制示例指令
                            </span>
                    </div>
                </div>
            </el-form-item>
        </el-form>
    </div>
</template>

<script setup lang="ts">
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
const formData = ref<any>({
    basis: {}
})
const currentKey = ref('')
const isChatGLM = computed(() => currentKey.value.includes('zhipu'))
const isXUNFEI = computed(() => currentKey.value === 'xunfei')
const isBAIDU = computed(() => currentKey.value === 'baidu')
const isApi2d = computed(() => currentKey.value.includes('api2d'))
const isGpt = computed(() => currentKey.value.includes('openai'))
const isChatGLM2 = computed(() => currentKey.value.includes('glm2'))
const isChatGLM3 = computed(() => currentKey.value.includes('glm3'))
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
