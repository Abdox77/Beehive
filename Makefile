
FRONTEND_DIR = frontend
BACKEND_DIR = backend


install:
	cd $(FRONTEND_DIR) && npm install

front:
	cd $(FRONTEND_DIR) && ng serve

back:
	cd $(BACKEND_DIR) && ng serve

