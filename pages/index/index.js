//index.js
//获取应用实例
const app = getApp()
Page({
	data: {
		userInfo: {},
		pageData: {
			title: '2018，你靠什么吃饭？',
			deauftText: '',
			imgBanner: {
				mode: "center",
				path: '../assets/images/banner.png'
			},
		}
	},
	//事件处理函数
	testTest: function () {
		wx.navigateTo({
			url: '../reault/reault?res=' + this.data.pageData.deauftText
		})
	},
	onLoad: function () {
		this.getUserInfo(this.userCallback).then((res) => {
			this.setData({
				'pageData.deauftText': res.nickName
			})
		})
	},
	getName: function (e) {
		this.setData({
			'pageData.deauftText': e.detail.value
		})
	},
	userCallback: function () {
		wx.showModal({
			title: '提示',
			content: '您还未授权，请先授权',
			showCancel: false,
			success: function (res) {
				wx.openSetting({
					success: function () {
						wx.getUserInfo({
							success: res => {
								app.globalData.userInfo = res.userInfo
								_this.setData({
									userInfo: res.userInfo
								})
								reslove(_this.data.userInfo)
							}
						})
					}
				})
			}
		})
	},
	getUserInfo: function (Callback) {
		var _this = this
		return new Promise(function (reslove, reject) {
			if (app.globalData.userInfo) {
				_this.setData({
					userInfo: app.globalData.userInfo
				})
				reslove(_this.data.userInfo)
			} else {
				// 在没有 open-type=getUserInfo 版本的兼容处理
				wx.getUserInfo({
					success: res => {
						app.globalData.userInfo = res.userInfo
						_this.setData({
							userInfo: res.userInfo
						})
						reslove(_this.data.userInfo)
					},
					fail: function () {
						Callback()
					}
				})
			}
		})
	}
})
