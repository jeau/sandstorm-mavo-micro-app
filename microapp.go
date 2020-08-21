package main

import (
	"html/template"
	"io/ioutil"
    "os"
	"log"
	"net/http"
	"regexp"
)

var urlPathRegex string = "^/(edit|save|view)/(([A-Z]+[a-z0-9]+)+)$"

type Page struct {
	Title string
	Body  []byte
    Access User
    PagesList []string
}

// User Sandstorm informations

type User struct {
     Nickname string `json:"nickname"`
     Name string `json:"name"`
     Permissions string `json:"permissions"`
     IsLogged bool `json:"isLogged"`
}

func userInfos( r *http.Request) (*User, error) {
    tab := r.Header.Get("X-Sandstorm-Tab-Id")
    nickname := r.Header.Get("X-Sandstorm-Preferred-Handle")
    username := r.Header.Get("X-Sandstorm-Username")
    permissions := r.Header.Get("X-Sandstorm-Permissions")
    login := ( permissions == "admin,edit,read" || tab == "")
    return &User{Nickname: nickname, Name: username, Permissions: permissions, IsLogged: login}, nil
 }

// Pages functions

func (p *Page) save() error {
	folder := "pages/" + p.Title
	filename := folder + "/index.html"
    _, err := os.Stat(folder)
    if os.IsNotExist(err) {
		errDir := os.MkdirAll(folder, 0755)
		if errDir != nil {
			log.Fatal(errDir)
		}
    }
	return ioutil.WriteFile(filename, p.Body, 0600)
}

func (p *Page) del() error {
	filename := "pages/" + p.Title
	return os.RemoveAll(filename)
}

func loadPage(title string) (*Page, error) {
	filename := "pages/" + title + "/index.html"
	body, err := ioutil.ReadFile(filename)
	if err != nil {
		return nil, err
	}
	return &Page{Title: title, Body: body}, nil
}

func listPages() ([]string) {
    file, err := os.Open("pages")
    if err != nil {
        log.Fatalf("failed opening directory: %s", err)
    }
    defer file.Close()
    list,_ := file.Readdirnames(0)
    return list
}


// Http handler

func viewHandler(w http.ResponseWriter, r *http.Request, title string) {
	p, err := loadPage(title)
	if err != nil {
        http.Redirect(w, r, "/edit/"+title, http.StatusFound)
		return
	}
    u, _ := userInfos(r)
    p.Access = *u
    p.PagesList = listPages()
	renderTemplate(w, "view", p)
}

func editHandler(w http.ResponseWriter, r *http.Request, title string) {
	p, err := loadPage(title)
	if err != nil {
		p = &Page{Title: title}
	}
	renderTemplate(w, "edit", p)
}

func saveHandler(w http.ResponseWriter, r *http.Request, title string) {
    body := r.FormValue("body")
    p := &Page{Title: title, Body: []byte(body)}
    if len(body) == 0 && title != "HomePage" {
        err := p.del()
        if err != nil {
            return
        }
        http.Redirect(w, r, "/view/HomePage", http.StatusFound)
    } else {
        err := p.save()
        if err != nil {
            http.Error(w, err.Error(), http.StatusInternalServerError)
            return
        }
        http.Redirect(w, r, "/view/"+title, http.StatusFound)
    }
}

// Pages render

var templates = template.Must(template.New("").Funcs(template.FuncMap{
    "safeHTML": func(b []byte) template.HTML {
        return template.HTML(b)
    },
}).ParseGlob("templates/*"))

func renderTemplate(w http.ResponseWriter, tmpl string, p *Page) {
    err := templates.ExecuteTemplate(w, tmpl, p)
    if err != nil {
        http.Error(w, err.Error(), http.StatusInternalServerError)
    }
}

// Web server 

var validPath = regexp.MustCompile(urlPathRegex)

func redirectHome(w http.ResponseWriter, r *http.Request) {
    http.Redirect(w, r, "/view/HomePage", http.StatusFound)
}

func makeHandler(fn func(http.ResponseWriter, *http.Request, string)) http.HandlerFunc {
    return func(w http.ResponseWriter, r *http.Request) {
        m := validPath.FindStringSubmatch(r.URL.Path)
        if m == nil {
            http.NotFound(w, r)
            return
        }
        fn(w, r, m[2])
    }
}

func main() {
    http.HandleFunc("/", redirectHome)
    http.HandleFunc("/view/", makeHandler(viewHandler))
    http.HandleFunc("/edit/", makeHandler(editHandler))
    http.HandleFunc("/save/", makeHandler(saveHandler))

	log.Fatal(http.ListenAndServe(":8000", nil))
}
