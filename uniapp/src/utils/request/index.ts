import HttpRequest from "./http";
import { merge } from "lodash-es";
import {
	HttpRequestOptions,
	RequestHooks,
	RequestConfig,
	RequestEventStreamConfig,
	RequestOptions,
	UploadFileOption,
} from "./type";
import { getToken } from "../auth";
import { RequestCodeEnum, RequestMethodsEnum } from "@/enums/requestEnums";
import { useUserStore } from "@/stores/user";
import router from "@/router";
import appConfig from "@/config";

export type {
	RequestConfig,
	RequestEventStreamConfig,
	RequestOptions,
	UploadFileOption,
};

const requestHooks: RequestHooks = {
	requestInterceptorsHook(options, config) {
		const { urlPrefix, baseUrl, withToken } = config;
		options.header = options.header || {};
		if (urlPrefix) {
			options.url = `${urlPrefix}${options.url}`;
		}
		if (baseUrl) {
			options.url = `${baseUrl}${options.url}`;
		}
		//#ifndef APP-PLUS
		const token = useUserStore().token || null;
		//#endif
		//#ifdef APP-PLUS
		const token = useUserStore().token || "null";
		//#endif
		// 添加token
		if (withToken && !options.header.token) {
			options.header.token = token;
		}
		options.header.version = appConfig.version;
		return options;
	},
	async responseInterceptorsHook(response, config, options) {
		const { isTransformResponse, isReturnDefaultResponse, isAuth } = config;

		//返回默认响应，当需要获取响应头及其他数据时可使用
		if (isReturnDefaultResponse) {
			return response;
		}
		// 是否需要对数据进行处理
		if (!isTransformResponse) {
			return response.data;
		}
		const { logout } = useUserStore();
		const { code, data, msg, show } = response.data as any;
		switch (code) {
			case RequestCodeEnum.SUCCESS:
				msg && show && uni.$u.toast(msg);
				return data;
			case RequestCodeEnum.FAILED:
				msg && show && uni.$u.toast(msg);
				return Promise.reject(msg);
			case RequestCodeEnum.TOKEN_INVALID:
				logout();
				if (
					(isAuth ||
						router.currentRoute.value.path.indexOf("ai_modules") >
							-1) &&
					!getToken()
				) {
					router.navigateTo({ path: "/pages/login/login" });
				}
				return Promise.reject(msg);

			default:
				return data;
		}
	},
	async responseInterceptorsCatchHook(options, error) {
		if (options.method?.toUpperCase() == RequestMethodsEnum.POST) {
			uni.$u.toast("请求失败，请重试");
		}
		return error.errMsg || error;
	},
};

const defaultOptions: HttpRequestOptions = {
	requestOptions: {
		timeout: appConfig.timeout,
	},
	baseUrl: appConfig.baseUrl,

	//是否返回默认的响应
	isReturnDefaultResponse: false,
	// 需要对返回数据进行处理
	isTransformResponse: true,
	// 接口拼接地址
	urlPrefix: appConfig.urlPrefix,
	// 忽略重复请求
	ignoreCancel: false,
	// 是否携带token
	withToken: true,
	isAuth: false,
	retryCount: 2,
	retryTimeout: 1000,
	requestHooks: requestHooks,
};

function createRequest(opt?: HttpRequestOptions) {
	return new HttpRequest(
		// 深度合并
		merge(defaultOptions, opt || {})
	);
}
const request = createRequest();
export default request;
