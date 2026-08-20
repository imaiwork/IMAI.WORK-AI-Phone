<template>
    <div class="edit-popup">
        <popup
            ref="popupRef"
            :title="popupTitle"
            :async="true"
            width="640px"
            @confirm="handleSubmit"
            @close="handleClose">
            <el-form ref="formRef" :model="formData" label-width="140px" :rules="formRules">
                <el-form-item label="支付方式" prop="config.mnp_pay_type">
                    <template v-if="formData.pay_way == PayWayEnum.WECHAT">
                        <div>
                            <el-radio-group v-model="formData.config.mnp_pay_type">
                                <el-radio :label="MnpPayType.NORMAL">微信支付</el-radio>
                                <el-radio :label="MnpPayType.VIRTUAL">虚拟支付</el-radio>
                            </el-radio-group>
                            <div class="form-tips">
                                此项影响微信小程序算力充值与 CDK 套餐在线支付；PC / H5 / App 仍走普通微信支付。
                            </div>
                            <div v-if="formData.config.mnp_pay_type === MnpPayType.VIRTUAL" class="form-tips">
                                选择虚拟支付后，请到「营销 → 充值套餐」和「财务 → CDK套餐」为每个套餐填写虚拟支付产品ID。
                            </div>
                        </div>
                    </template>
                    <el-radio v-else :label="popupTitle" :model-value="popupTitle" />
                </el-form-item>
                <el-form-item label="显示名称" prop="name">
                    <el-input v-model="formData.name" placeholder="请输入显示名称" />
                </el-form-item>
                <el-form-item label="显示图标" prop="image">
                    <div>
                        <material-picker :limit="1" :disabled="false" v-model="formData.icon" />
                        <span class="form-tips">建议尺寸：200*200px</span>
                    </div>
                </el-form-item>
                <template v-if="formData.pay_way == PayWayEnum.WECHAT">
                    <el-form-item prop="config.interface_version" label="微信支付接口版本">
                        <div>
                            <el-radio-group v-model="formData.config.interface_version">
                                <el-radio label="v3"></el-radio>
                            </el-radio-group>
                            <div class="form-tips">暂时只支持V3版本</div>
                        </div>
                    </el-form-item>

                    <el-form-item label="商户类型" prop="config.merchant_type">
                        <div>
                            <el-radio-group v-model="formData.config.merchant_type">
                                <el-radio label="ordinary_merchant">普通商户</el-radio>
                            </el-radio-group>
                            <div class="form-tips">暂时只支持普通商户类型，服务商户类型模式暂不支持</div>
                        </div>
                    </el-form-item>

                    <el-form-item label="微信支付商户号" prop="config.mch_id">
                        <div class="flex-1">
                            <el-input v-model="formData.config.mch_id" placeholder="请输入微信支付商户号" />
                            <div class="form-tips">微信支付商户号（MCHID）</div>
                        </div>
                    </el-form-item>

                    <el-form-item label="商户API密钥" prop="config.pay_sign_key">
                        <el-input v-model="formData.config.pay_sign_key" placeholder="请输入微信支付商户API密钥" />
                        <span class="form-tips">微信支付商户API密钥（paySignKey）</span>
                    </el-form-item>

                    <el-form-item label="微信支付证书" prop="config.apiclient_cert">
                        <el-input
                            type="textarea"
                            rows="3"
                            v-model="formData.config.apiclient_cert"
                            placeholder="请输入微信支付证书" />
                        <span class="form-tips">
                            微信支付证书（apiclient_cert.pem），前往微信商家平台生成并黏贴至此处
                        </span>
                    </el-form-item>

                    <el-form-item label="微信支付证书密钥" prop="config.apiclient_key">
                        <el-input
                            type="textarea"
                            rows="3"
                            v-model="formData.config.apiclient_key"
                            placeholder="请输入微信支付证书密钥" />
                        <span class="form-tips">
                            微信支付证书密钥（apiclient_key.pem），前往微信商家平台生成并黏贴至此处
                        </span>
                    </el-form-item>

                    <template v-if="formData.config.mnp_pay_type === MnpPayType.VIRTUAL">
                        <el-form-item label="OfferID" prop="config.mnp_virtual_pay.offer_id" required>
                            <div class="flex-1">
                                <el-input
                                    v-model="formData.config.mnp_virtual_pay.offer_id"
                                    placeholder="请输入虚拟支付 OfferID" />
                                <div class="form-tips">小程序后台 → 虚拟支付 → 基本配置中的 offerId</div>
                            </div>
                        </el-form-item>
                        <el-form-item label="AppKey" prop="config.mnp_virtual_pay.app_key" required>
                            <div class="flex-1">
                                <el-input
                                    v-model="formData.config.mnp_virtual_pay.app_key"
                                    placeholder="请输入虚拟支付 AppKey" />
                                <div class="form-tips">小程序后台 → 虚拟支付 → 基本配置中的 AppKey</div>
                            </div>
                        </el-form-item>
                    </template>

                    <el-form-item label="支付授权目录">
                        <div>
                            <div>
                                <span class="mr-[20px]">{{ formData.domain }}</span>
                                <el-button link type="primary" v-copy="formData.domain">复制</el-button>
                            </div>
                            <span class="form-tips">支付授权目录仅用于参考，复制后前往微信商家平台填写</span>
                        </div>
                    </el-form-item>
                </template>
                <template v-if="formData.pay_way == PayWayEnum.ALIPAY">
                    <el-form-item label="模式" prop="config.mode">
                        <div>
                            <el-radio-group v-model="formData.config.mode">
                                <el-radio label="normal_mode">普通模式</el-radio>
                            </el-radio-group>
                            <div class="form-tips">暂时仅支持支付宝普通模式</div>
                        </div>
                    </el-form-item>

                    <el-form-item label="商户类型" prop="config.merchant_type">
                        <div>
                            <el-radio-group v-model="formData.config.merchant_type">
                                <el-radio label="ordinary_merchant">普通商户</el-radio>
                            </el-radio-group>
                            <div class="form-tips">暂时只支持普通商户类型，服务商户类型模式暂不支持</div>
                        </div>
                    </el-form-item>

                    <el-form-item label="应用ID" prop="config.app_id">
                        <div class="flex-1">
                            <el-input v-model="formData.config.app_id" placeholder="请输入支付宝应用ID" />
                            <span class="form-tips">支付宝应用APP_ID</span>
                        </div>
                    </el-form-item>

                    <el-form-item label="应用私钥" prop="config.private_key">
                        <div class="flex-1">
                            <el-input
                                type="textarea"
                                rows="3"
                                v-model="formData.config.private_key"
                                placeholder="请输入支付宝应用私钥" />
                            <span class="form-tips">支付宝应用私钥（private_key）</span>
                        </div>
                    </el-form-item>

                    <el-form-item label="支付宝公钥" prop="config.ali_public_key">
                        <div class="flex-1">
                            <el-input
                                type="textarea"
                                rows="3"
                                v-model="formData.config.ali_public_key"
                                placeholder="请输入支付宝公钥" />
                            <span class="form-tips">支付宝公钥（ali_public_key）</span>
                        </div>
                    </el-form-item>
                </template>
                <el-form-item label="排序" prop="sort">
                    <div>
                        <el-input-number v-model="formData.sort" :min="0" :max="9999" />
                        <div class="form-tips">默认为0， 数值越大越排前</div>
                    </div>
                </el-form-item>
            </el-form>
        </popup>
    </div>
</template>
<script lang="ts" setup>
import type { FormInstance, FormRules } from "element-plus";
import { getPayConfig, setPayConfig } from "@/api/setting/pay";
import Popup from "@/components/popup/index.vue";

const emit = defineEmits(["success", "close"]);
const formRef = shallowRef<FormInstance>();
const popupRef = shallowRef<InstanceType<typeof Popup>>();

enum PayWayEnum {
    BALANCE = 1,
    WECHAT = 2,
    ALIPAY = 3,
}

/** 小程序支付方式：1普通支付 2虚拟支付 */
enum MnpPayType {
    NORMAL = 1,
    VIRTUAL = 2,
}

const DEFAULT_MNP_VIRTUAL_PAY = {
    offer_id: "",
    app_key: "",
    currency_type: "CNY", // 固定 CNY，不在表单展示
};

const popupTitle = computed(() => {
    switch (formData.pay_way) {
        case PayWayEnum.BALANCE:
            return "余额支付";
        case PayWayEnum.WECHAT:
            return "微信支付";
        case PayWayEnum.ALIPAY:
            return "支付宝支付";
    }
});

const formData = reactive({
    id: "",
    pay_way: 0,
    name: "",
    icon: "",
    sort: 0,
    remark: "",
    domain: "",
    config: {
        interface_version: "",
        merchant_type: "",
        mch_id: "",
        pay_sign_key: "",
        apiclient_cert: "",
        apiclient_key: "",
        mnp_pay_type: MnpPayType.NORMAL as number,
        mnp_virtual_pay: { ...DEFAULT_MNP_VIRTUAL_PAY },
        mode: "",
        app_id: "",
        private_key: "",
        ali_public_key: "",
    },
});

const requireVirtualPayField = (label: string) => {
    return (_rule: any, value: any, callback: (error?: Error) => void) => {
        if (formData.config.mnp_pay_type !== MnpPayType.VIRTUAL) {
            callback();
            return;
        }
        if (value === undefined || value === null || String(value).trim() === "") {
            callback(new Error(`请输入${label}`));
            return;
        }
        callback();
    };
};

const formRules: FormRules = {
    name: [
        {
            required: true,
            message: "请输入显示名称",
        },
    ],
    "config.mch_id": [
        {
            required: true,
            message: "请输入微信支付商户号",
        },
    ],
    "config.pay_sign_key": [
        {
            required: true,
            message: "请输入微信支付商户API密钥",
        },
    ],
    "config.apiclient_cert": [
        {
            required: true,
            message: "请输入微信支付证书",
        },
    ],
    "config.apiclient_key": [
        {
            required: true,
            message: "请输入微信支付证书密钥",
        },
    ],
    "config.mnp_pay_type": [
        {
            validator: (_rule, value, callback) => {
                if (formData.pay_way !== PayWayEnum.WECHAT) {
                    callback();
                    return;
                }
                if (value !== MnpPayType.NORMAL && value !== MnpPayType.VIRTUAL) {
                    callback(new Error("请选择支付方式"));
                    return;
                }
                callback();
            },
            trigger: "change",
        },
    ],
    "config.mnp_virtual_pay.offer_id": [
        {
            required: true,
            validator: requireVirtualPayField("OfferID"),
            trigger: ["blur", "change"],
        },
    ],
    "config.mnp_virtual_pay.app_key": [
        {
            required: true,
            validator: requireVirtualPayField("AppKey"),
            trigger: ["blur", "change"],
        },
    ],
    "config.app_id": [
        {
            required: true,
            message: "请输入支付宝应用ID",
        },
    ],
    "config.private_key": [
        {
            required: true,
            message: "请输入支付宝应用私钥",
        },
    ],
    "config.ali_public_key": [
        {
            required: true,
            message: "请输入支付宝公钥",
        },
    ],
};

const handleSubmit = async () => {
    await formRef.value?.validate();
    await setPayConfig(formData);
    popupRef.value?.close();
    emit("success");
};

const open = () => {
    popupRef.value?.open();
};

const normalizeConfig = (config: Record<string, any> = {}) => {
    const mnpPayType = Number(config.mnp_pay_type);
    return {
        ...formData.config,
        ...config,
        mnp_pay_type: mnpPayType === MnpPayType.VIRTUAL ? MnpPayType.VIRTUAL : MnpPayType.NORMAL,
        mnp_virtual_pay: {
            offer_id: config.mnp_virtual_pay?.offer_id ?? DEFAULT_MNP_VIRTUAL_PAY.offer_id,
            app_key: config.mnp_virtual_pay?.app_key ?? DEFAULT_MNP_VIRTUAL_PAY.app_key,
            currency_type: DEFAULT_MNP_VIRTUAL_PAY.currency_type,
        },
    };
};

const setFormData = (data: Record<any, any>) => {
    for (const key in formData) {
        if (data[key] == null || data[key] == undefined) continue;
        if (key === "config") {
            formData.config = normalizeConfig(data.config);
        } else {
            //@ts-ignore
            formData[key] = data[key];
        }
    }
};

const getDetail = async (row: Record<string, any>) => {
    const data = await getPayConfig({
        id: row.id,
    });
    setFormData(data);
};

const handleClose = () => {
    emit("close");
};

defineExpose({
    open,
    setFormData,
    getDetail,
});
</script>
