//logs.js

Page({
	onLoad: function () {
		this.setData({
			'name_header.userName': wx.getStorageSync('needData').val,
			'name_header.userHeader': wx.getStorageSync('needData').avatar
		})
	}
})
