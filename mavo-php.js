Mavo.Backend.register(Bliss.Class({
	extends: Mavo.Backend,
	id: "php",
	constructor: function() {
		this.permissions.on(["edit", "save", "read"]);
		this.key = this.mavo.id;
		this.phpFile = new URL('mavo-backend.php', Mavo.base);
		
		this.user = false;
		this.login(true);
	},
	
	// Low-level saving code.
	// serialized: Data serialized according to this.format
	// path: Path to store data
	// o: Arbitrary options
	put: function(serialized, path = this.path, o = {}) {
		// new URL() to clone phpFile url
		var postUrl = new URL(this.phpFile);
		// Send appID
		postUrl.searchParams.set('id', this.key);
		// Send filename
		postUrl.searchParams.set('source', this.source);
		// Default action to 'putData'
		postUrl.searchParams.set('action', 'putData');
		// Add all the arbitrary things
		for (var opt in o) {
		    postUrl.searchParams.set(opt, o[opt]);
		}
		// Return POST request to server
		return this.request(postUrl, serialized, 'POST');
	},
    // Src : https://github.com/mavoweb/mavo/blob/master/src/backend.github.js
	upload: function(file, path = this.path) {
		return Mavo.readFile(file)
			.then(dataURL => {
				var base64 = dataURL.slice(5); // remove data:
				var media = base64.match(/^\w+\/[\w+]+/)[0];
				base64 = base64.replace(RegExp(`^${media}(;base64)?,`), "");
				
				return this.put(base64, path, {
					action: 'putFile',
					file: file.name,
					path: path
				});
			})
			//Resolve with file name on server
			.then((fileData) => fileData.data.file);
	},


	static: {
		test: value => value == "php"
	}
}));
