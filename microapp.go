package main

import (
    "html/template"
    "io/ioutil"
    "os"
    "log"
    "net/http"
    "regexp"
    "encoding/json"
    "net/url"
    "encoding/base64"
)

var urlPathRegex string = "^/(admin|edit|save|view)/(([A-Z]+[a-z0-9]+)+)$"
var dataFileRegex string = "^data/[0-9a-zA-Z-._]+.(json|csv|tsv|txt)$"
var mediaFileRegex string = "^/(audio|image|video)s/([0-9a-zA-Z-._]+.(aac|aif|aiff|asf|avi|bmp|gif|ico|jp2|jpe|jpeg|jpg|m4a|m4v|mov|mp2|mp3|mp4|mpa|mpe|mpeg|mpg|png|tif|tiff|wav|webm|wma|wmv))$"

type Page struct {
    Title string
    Body  []byte
    Access User
    PagesList []string
}

type Response struct {
    Status bool `json:"status"`
    Data User `json:"data"`
}

type User struct {
    Nickname string `json:"nickname"`
    Name string `json:"name"`
    Picture string `json:"avatar"`
    Permissions string `json:"permissions"`
    IsLogged bool `json:"isLogged"`
    Login string `json:"login"`
}

func userInfos( r *http.Request) (*User, error) {
    tab := r.Header.Get("X-Sandstorm-Tab-Id")
    nickname := r.Header.Get("X-Sandstorm-Preferred-Handle")
    username, _ := url.QueryUnescape(r.Header.Get("X-Sandstorm-Username"))
    picture := r.Header.Get("X-Sandstorm-User-Picture")
    permissions := r.Header.Get("X-Sandstorm-Permissions")
    isAuthorised, _ := regexp.MatchString("admin|edit", permissions)
    isLogged := (isAuthorised || tab == "")
    return &User{Nickname: nickname, Name: username, Picture: picture, Permissions: permissions, IsLogged: isLogged}, nil
}

func check(e error) {
    if e != nil {
        panic(e)
    }
}


// Pages functions

func (p *Page) save() error {
    folder := "pages/" + p.Title
    filename := folder + "/index.html"
    _, err := os.Stat(folder)
    if os.IsNotExist(err) {
        err := os.MkdirAll(folder, 0755)
        check(err)
    }
    return ioutil.WriteFile(filename, p.Body, 0600)
}

func (p *Page) del() error {
    filename := "pages/" + p.Title
    return os.RemoveAll(filename)
}

func loadPage(title string) (*Page, error) {
    filename := "pages/" + title + "/index.html"
    _,err := os.Stat(filename)
    if os.IsNotExist(err) {
        return &Page{Title: title}, err
    }
    body, err := ioutil.ReadFile(filename)
    check(err)
    return &Page{Title: title, Body: body}, nil
}

func listPages() ([]string) {
    file, err := os.Open("pages")
    check(err)
    defer file.Close()
    list,_ := file.Readdirnames(0)
    return list
}

// Http handler

func viewHandler(w http.ResponseWriter, r *http.Request, title string) {
    u, _ := userInfos(r)
    p, err := loadPage(title)
    if err != nil {
        if u.IsLogged {
            http.Redirect(w, r, "/edit/"+title, http.StatusFound)
        } else {
            http.Redirect(w, r, "/"+r.URL.Path, http.StatusFound)
        }
        return
    }
    p.Access = *u
    p.PagesList = listPages()
    renderTemplate(w, "view", p)
}

func editHandler(w http.ResponseWriter, r *http.Request, title string) {
    p, err := loadPage(title)
    if err != nil {
        p = &Page{Title: title }
    }
    renderTemplate(w, "edit", p)
}

func createHandler(w http.ResponseWriter, r *http.Request) {
    newpage := r.URL.Query().Get("newpage")
    http.Redirect(w, r, "/view/"+newpage, http.StatusFound)
}

func saveHandler(w http.ResponseWriter, r *http.Request, title string) {
    body := r.FormValue("body")
    p := &Page{Title: title, Body: []byte(body)}
    if len(body) == 0 && title != "HomePage" {
        err := p.del()
        check(err)
        http.Redirect(w, r, "/view/HomePage", http.StatusFound)
    } else {
        err := p.save()
        check(err)
        http.Redirect(w, r, "/view/"+title, http.StatusFound)
    }
}

func adminHandler(w http.ResponseWriter, r *http.Request, title string) {
    p, err := loadPage(title)
    check(err)
    p.PagesList = listPages()
    renderTemplate(w, "admin", p)
}

//Mavo backend Handler returns http response in JSON format

func backendHandler(w http.ResponseWriter, r *http.Request) {
    response := resultAction(r)
    j, _ := json.Marshal(response)
    w.Write(j)
}

func resultAction(r *http.Request) Response {
    u, err := userInfos(r)
    check(err)
    var status bool = false
    a := r.URL.Query().Get("action")
    if (u.IsLogged == true) {
        switch a {
        case "login":
            u.IsLogged = true
            status = true
        case "logout":
            u.IsLogged = false
            status = true
        case "putData":
            source := r.URL.Query().Get("source")
            validData, _ := regexp.Compile(dataFileRegex)
            if validData.MatchString(source) {
                body, err := ioutil.ReadAll(r.Body)
                check(err)
                _, errs := os.Stat(source)
                if os.IsNotExist(errs) {
                    f,err := os.Create(source)
                    check(err)
                    defer f.Close()
                }
                err = ioutil.WriteFile(source, body, 0600)
                check(err)
                status = true
            } else {
                status = false
            }
        case "putFile":
            path := r.URL.Query().Get("path")
            validMedia, _ := regexp.Compile(mediaFileRegex)
            if validMedia.MatchString(path) {
                body, err := ioutil.ReadAll(r.Body)
                check(err)
                dec, err := base64.StdEncoding.DecodeString(string(body))
                check(err)
                _, errs := os.Stat(path)
                if os.IsNotExist(errs) {
                    f,err := os.Create(path)
                    check(err)
                    defer f.Close()
                }
                err = ioutil.WriteFile(path, dec, 0600)
                check(err)
                status = true
            } else {
                status = false
            }
        }
    } else {
        switch a {
        case "login":
            status = true
        }
    }
    return Response {Status: status, Data: *u}
}

// Pages render

var templates = template.Must(template.New("").Funcs(template.FuncMap{
    "safeHTML": func(b []byte) template.HTML {
        return template.HTML(b)
    },
}).ParseGlob("templates/*"))

func renderTemplate(w http.ResponseWriter, tmpl string, p *Page) {
    err := templates.ExecuteTemplate(w, tmpl, p)
    check(err)
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
    resources := []string{"assets", "audios", "data", "images", "videos"}
    for _, value := range resources {
        http.Handle("/view/"+value+"/", http.StripPrefix("/view/"+value+"/", http.FileServer(http.Dir(value+"/"))))
        http.Handle("/"+value+"/", http.StripPrefix("/"+value+"/", http.FileServer(http.Dir(value+"/"))))
    }
    http.HandleFunc("/view/", makeHandler(viewHandler))
    http.HandleFunc("/edit/", makeHandler(editHandler))
    http.HandleFunc("/save/", makeHandler(saveHandler))
    http.HandleFunc("/admin/", makeHandler(adminHandler))
    http.HandleFunc("/create/", createHandler)
    http.HandleFunc("/backend", backendHandler)
    http.HandleFunc("/", redirectHome)

    log.Fatal(http.ListenAndServe(":8000", nil))
}
