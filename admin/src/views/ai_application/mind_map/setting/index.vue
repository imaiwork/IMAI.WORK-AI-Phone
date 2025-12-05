<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <div class="text-xl font-medium mb-[20px]">提示词配置</div>
            <el-form :model="promptConfig">
                <el-form-item label="提示词">
                    <div>
                        <el-input
                            v-model="promptConfig.prompt_text"
                            type="textarea"
                            placeholder="请输入提示词"
                            class="w-[500px]"
                            :rows="20" />
                        <div class="mt-2">
                            <el-button
                                type="primary"
                                link
                                v-copy="
                                    `
							角色(Role):
							你是一位思维导图设计专家,擅长将长篇文章、讲座内容、会议录音等不同形式的长文本,转化为结构清晰、层次分明的思维导图。你能快速提炼文本的核心内容和关键信息,并使用Markdown格式对其进行有效地组织和呈现,使之成为一份可直接导入思维导图软件并生成美观实用脑图的蓝本。

							背景(Background):
							在信息爆炸的时代,人们每天接收和处理海量信息,提炼关键内容和梳理逻辑结构成为重要的能力。思维导图是一种行之有效的信息整理和学习工具,但从零开始制作一张高质量的思维导图并非易事。将长文本内容快速转化为思维导图的需求日益增长。

							任务(Task):
							你的任务是将【用户原始需求描述】比如提供的长篇文章、讲座内容、会议录音等长文本,转化为以Markdown格式呈现的思维导图蓝本。你需要仔细阅读或聆听材料,快速提炼出核心内容和关键信息,并运用Markdown的各种格式元素(如标题、列表、粗斜体等),对内容进行层次清晰的组织和排版,使之成为一份可直接导入思维导图软件、一键生成美观实用脑图的蓝本。

							规则与限制(Rules & Restrictions):
							输出的思维导图蓝本必须严格遵循Markdown语法规范。
							思维导图的结构层次要清晰、缜密,主次分明,确保生成的脑图一目了然。
							思维导图的内容必须准确、完整地反映原文本的核心内容,不得遗漏关键信息。
							每个节点的内容要简洁明了,避免冗长或模棱两可的表述。
							要善于运用Markdown的格式元素,提高思维导图蓝本的可读性和美观度。
							禁止生成任何违法、违规、色情、暴力或冒犯性的内容。
							参考短语(Reference sentences):
							逻辑清晰、结构缜密
							主次分明、层次鲜明
							提炼精准、重点突出
							简洁明了、一目了然
							排版美观、格式规范
							忠于原文、不遗核心
							一键生成、即取即用

							风格和语气(Style & Tone):
							思维导图蓝本的整体风格应简洁明快、专业实用。语言表达要准确、干练,避免使用过于口语化或随意的表述。在保证内容完整、结构清晰的同时,也要注重排版的美观和可读性,力求为用户提供一份高质量的、即取即用的思维导图蓝本。

							受众群体(Audience):
							思维导图蓝本的目标用户主要是需要快速对长文本内容进行梳理提炼、生成思维导图的学生、职场人士、研究者等。他们希望能借助AI的力量,将海量信息快速转化为清晰有序、一目了然的思维导图,以提高学习和工作效率。

							输出格式(Output format):
							以Markdown格式输出思维导图蓝本,其中:

							根节点(中心主题)使用一级标题(#)
								一级分支节点使用二级标题(##)
									二级及以下分支节点使用列表(-、1. 等)
							关键词使用粗体(**)或斜体(*)标注
							确保生成的Markdown文本层次分明、格式规范,可直接导入主流思维导图软件并一键生成美观实用的脑图。
							工作流程(Workflow):
							仔细阅读或聆听【用户原始需求描述】,快速提炼出核心内容和关键信息。
							根据提炼出的内容,确定思维导图的整体结构和层次关系。
							使用Markdown格式对提炼出的内容进行组织和排版,形成初步的思维导图蓝本。
							检查并润色思维导图蓝本,确保其内容完整、结构清晰、格式规范。
							以Markdown格式输出最终的思维导图蓝本。
							询问用户是否还有其他需求或反馈,根据反馈进一步优化思维导图蓝本。
							初始化(Initialization):

							【用户原始需求描述】= < {{input}} >;
							根据上面的用户原始需求描述，按上面的提示词原则，必须用规定<输出格式>来输出；不要输出其它任何无关内容；`
                                ">
                                复制示例指令
                            </el-button>
                            如果示例指令效果不明显，或者效果不好，可自行调整提示词，<el-tag
                                type="danger"
                                v-html="`{{input}}`"></el-tag
                            >为用户输入的原始需求描述，切记保留
                        </div>
                    </div>
                </el-form-item>
            </el-form>
            <div class="">
                <el-button type="primary" @click="lockSavePromptConfig" :loading="isSavePromptConfig">保存</el-button>
            </div>
        </el-card>
    </div>
</template>

<script setup lang="ts">
import { getCreditSet, setCreditSet } from "@/api/marketing/creditset";
import { getChatPrompt, saveChatPrompt } from "@/api/chat";
import feedback from "@/utils/feedback";
import { useLockFn } from "@/hooks/useLockFn";

const promptConfig = ref<any>({});

const getChatPromptConfig = async () => {
    const data = await getChatPrompt();
    promptConfig.value = data.find((item: any) => item.id === 2);
};

const savePromptConfig = async () => {
    await saveChatPrompt(promptConfig.value);
};

const { lockFn: lockSavePromptConfig, isLock: isSavePromptConfig } = useLockFn(savePromptConfig);

getChatPromptConfig();
</script>

<style scoped></style>
