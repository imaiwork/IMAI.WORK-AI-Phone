<template>
	<div
		class="h-full bg-white rounded-[20px] overflow-x-auto dynamic-scroller">
		<div class="h-full flex flex-col min-w-[800px]">
			<!-- 头部导航 -->
			<div
				class="flex-shrink-0 flex items-center justify-between px-[14px] h-[88px] border-b border-[#0000000d]">
				<div
					class="flex items-center gap-2 cursor-pointer"
					@click="closePanel">
					<Icon name="el-icon-ArrowLeft"></Icon>
					<div>返回上一步</div>
				</div>
				<div class="flex items-center gap-1">
					<ElButton
						type="primary"
						class="!rounded-full !h-10 w-[98px]"
						:loading="isSubmitting"
						@click="submitForm">
						提交
					</ElButton>
				</div>
			</div>
			<div class="p-3">
				<div class="grid grid-cols-2 xl:grid-cols-3 gap-4">
					<div
						v-for="item in typeTabs"
						:key="item.type"
						class="rounded-xl p-4 cursor-pointer"
						:class="[
							currentType == item.type
								? 'bg-[#0065fb0d] shadow-[0_0_0_1px_var(--color-primary)]'
								: 'bg-[#f6f6f6] shadow-[0_0_0_1px_#EFEFEF]',
						]"
						@click="handleTypeChange(item.type)">
						<div class="flex items-center gap-2">
							<div
								class="flex-shrink-0 w-[50px] h-[50px] flex items-center justify-center rounded-md border border-[#0000001a]"
								:class="[
									currentType == item.type
										? 'bg-[#0065FB] text-white'
										: 'text-black',
								]">
								<Icon :name="item.icon" :size="24"></Icon>
							</div>
							<div>
								<div class="text-base">{{ item.name }}</div>
								<div class="text-[#00000080]">
									{{ item.desc }}
								</div>
							</div>
						</div>
						<div class="text-[#00000080] mt-3">
							{{ item.uploadType }}
						</div>
					</div>
				</div>
			</div>
			<!-- 内容区域 -->
			<div class="grow min-h-0 flex flex-col">
				<template v-for="item in typeTabs" :key="item.type">
					<component
						:is="item.component"
						v-model="item.data"
						v-show="item.type == currentType" />
				</template>
			</div>
		</div>
	</div>
</template>

<script setup lang="ts">
import { vectorKnowledgeBaseFileAdd } from "@/api/knowledge_base";
import type { IDataItem } from "./import/hook";
import Doc from "./import/doc.vue";
import Csv from "./import/csv.vue";
import Qa from "./import/qa.vue";
import Web from "./import/web.vue";

// ======================= 组件props和emits =======================

const props = withDefaults(
	defineProps<{
		knName: string | string[]; // 知识库名称
		knId: string | string[]; // 知识库ID
	}>(),
	{
		knName: "",
		knId: "",
	}
);

const emit = defineEmits<{
	(e: "back"): void; // 返回事件
	(e: "success"): void; // 提交成功事件
}>();

// ======================= 数据和状态管理 =======================

/**
 * @description 定义导入类型的枚举
 */
enum TypeEnum {
	DOCUMENT = 1, // 通用文档
	QUESTION = 2, // 问答对
	AUTO_QUESTION = 3, // 自动拆分问答对
	WEB = 4, // 网页
}

/**
 * @description 定义不同导入类型的标签页配置
 * 每个对象包含类型、名称、描述、支持的文件类型、图标、对应的组件和数据模型
 */
const typeTabs = ref([
	{
		type: TypeEnum.DOCUMENT,
		name: "通用文档导入",
		desc: "选择文本文件，直接按其分段进行处理",
		uploadType: "支持 .txt , docx, .pdf, .md",
		icon: "local-icon-document2",
		component: markRaw(Doc),
		data: [] as IDataItem[],
	},
	{
		type: TypeEnum.QUESTION,
		name: "问答对导入",
		desc: "批量导入问答对，效果最佳",
		uploadType: "支持 .excel , csv",
		icon: "local-icon-word",
		component: markRaw(Csv),
		data: [] as IDataItem[],
	},
	{
		type: TypeEnum.WEB,
		name: "网页解析",
		desc: "输入网页链接，快速导入内容",
		uploadType: "支持 url链接",
		icon: "local-icon-sand_lock",
		component: markRaw(Web),
		data: [] as IDataItem[],
	},
]);

// 当前选中的导入类型，默认为通用文档
const currentType = ref<TypeEnum>(TypeEnum.DOCUMENT);

// ======================= 方法 =======================

/**
 * @description 切换导入类型
 * @param {TypeEnum} type - 目标类型
 */
const handleTypeChange = (type: TypeEnum) => {
	currentType.value = type;
};

/**
 * @description 关闭当前面板，触发 back 事件
 */
const closePanel = () => {
	emit("back");
};

/**
 * @description 提交表单
 * 使用 useLockFn 防止重复提交
 */
const { lockFn: submitForm, isLock: isSubmitting } = useLockFn(async () => {
	const currentTab = typeTabs.value.find(
		(item) => item.type == currentType.value
	);
	const isNull = currentTab.data.every((item) => !item.data.length);
	if (!currentTab || !currentTab.data.length || isNull) {
		feedback.msgWarning("请先添加数据");
		return;
	}

	try {
		await vectorKnowledgeBaseFileAdd({
			kb_id: props.knId as string,
			method: currentType.value,
			documents: currentTab.data,
		});
		feedback.msgSuccess("添加成功");
		emit("success");
		closePanel();
	} catch (error) {
		feedback.msgError(error as string);
	}
});

// ======================= 生命周期钩子 =======================

/**
 * @description 处理页面刷新或关闭前的提示
 */
const handleBeforeUnload = (event: BeforeUnloadEvent) => {
	event.preventDefault();
	event.returnValue = "请勿刷新页面";
	return "请勿刷新页面";
};

onMounted(() => {
	window.addEventListener("beforeunload", handleBeforeUnload);
});

onBeforeUnmount(() => {
	window.removeEventListener("beforeunload", handleBeforeUnload);
});
</script>

<style scoped lang="scss">
:deep(.el-upload) {
	.el-upload-dragger {
		@apply bg-[#f6f6f6] border-solid;
	}
}
:deep(.el-upload-list) {
	.el-upload-list__item {
		@apply h-11 flex items-center shadow-[0_0_0_1px_#EFEFEF];
	}
	.el-progress {
		@apply top-[34px] left-0;
	}
}
</style>
