//logs.js

Page({
	onLoad: function () {
		this.setData({
			'name_header.userName': wx.getStorageSync('props').val,
			'name_header.userHeader': wx.getStorageSync('props').avatar
		})
	}
})
