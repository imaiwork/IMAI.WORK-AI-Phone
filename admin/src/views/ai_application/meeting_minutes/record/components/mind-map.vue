<template>
	<div class="h-full relative">
		<template v-if="formData.reply">
			<div ref="toolbarContainer"></div>
			<svg ref="mindMapContainer" class="w-full h-full"></svg>
		</template>
		<template v-else>
			<div class="flex flex-col justify-center gap-4 items-center h-full">
				<el-empty />
			</div>
		</template>
	</div>
</template>

<script setup lang="ts">
import { Transformer } from "markmap-lib";
import { Markmap } from "markmap-view";
import { Toolbar } from "markmap-toolbar";

const props = defineProps<{
	detail: any;
}>();

const formData = reactive({
	reply: "",
});

const mindMapContainer = shallowRef<SVGSVGElement | null>(null);
const toolbarContainer = ref(null);

let markmap: Markmap = null;
const transformer = new Transformer();
const initMindMap = async () => {
	await nextTick();
	markmap = Markmap.create(mindMapContainer.value, {
		autoFit: true,
		color: (node: any) => "#2353f4",
	});
	const toolbar = new Toolbar();
	toolbar.attach(markmap);
	toolbarContainer.value.appendChild(toolbar.el);
	const { root } = transformer.transform(formData.reply);
	markmap?.setData(root);
	markmap?.fit();
};

// 获取思维导图
const getMindMap = async () => {
	const { response } = props.detail;
	const lists =
		response?.Result?.Summarization?.Summarization?.MindMapSummary;
	const title =
		response?.Result?.Summarization?.Summarization?.ParagraphTitle;
	const markdown = convertToMarkdown({ title, lists });
	if (lists.length) {
		formData.reply = markdown;
		initMindMap();
	}
};

function convertToMarkdown(data: any) {
	let markdown = "";
	data.lists.forEach((list) => {
		markdown += `# ${list.Title}\n\n`;
		list.Topic.forEach((topic) => {
			markdown += `### ${topic.Title}\n\n`;
			topic.Topic.forEach((subTopic) => {
				markdown += `#### ${subTopic.Title}\n\n`;
			});
		});
	});
	return markdown;
}

onMounted(() => {
	getMindMap();
});
</script>

<style scoped>
#mindMapContainer * {
	margin: 0;
	padding: 0;
}
</style>
