# -*- coding: utf-8 -*-
import requests
import md5
import sys
import json
import logging
import random
#URL = "http://api.momo.im"
URL = "http://192.168.99.100:8080"


headers = {
    "Content-Type":"application/json"
}

obj = {
    "mobile":"13800000001",
    "zone_code":"86",
    "password":"123456",
}

url = URL + "/user/login"
print url
resp = requests.post(url, data=json.dumps(obj), headers=headers)
assert(resp.status_code == 200)
r = json.loads(resp.content)
uid1 = r["id"]
token1 = r["access_token"]

obj = {
    "mobile":"13800000000",
    "zone_code":"86",
    "password":"123456",
}

url = URL + "/user/login"
print url
resp = requests.post(url, data=json.dumps(obj), headers=headers)

assert(resp.status_code == 200)
r = json.loads(resp.content)
token0 = r["access_token"]
token = token0
print "token:", token
uid0 = r["id"]


headers = {
    "Content-Type":"application/json",
    "Authorization":"Bearer " + token
}

#成为好友
url = URL + "/friend/isfriend/%s"%uid1
resp = requests.get(url, headers=headers)
assert(resp.status_code == 200)
r = json.loads(resp.content)
if not r["status"]:
    url = URL + "/friend/add"
    obj = {"user_id":uid1}
    resp = requests.post(url, data=json.dumps(obj), headers=headers)
    assert(resp.status_code == 200)
    

def get_status(token):

    headers = {
        "Content-Type":"application/json",
        "Authorization":"Bearer " + token
    }

    url = URL + "/statuses/index.json"
    resp = requests.get(url, headers=headers)
     
    assert(resp.status_code == 200)
    obj = json.loads(resp.content)
    #print json.dumps(obj, sort_keys=True, indent=4, separators=(',', ': '))
    if obj.has_key("data") and len(obj["data"]) > 0:
        latest = obj["data"][0]["modified_at"]
    else:
        latest = 0
    return latest

latest0 = get_status(token)
latest1 = get_status(token1)

def post_record(token, send_text):
    headers = {
        "Content-Type":"application/json",
        "Authorization":"Bearer " + token
    }

    url = URL + "/record/create.json"
    obj = {
        "text":send_text,
        "sync":False,
    }
    resp = requests.post(url, data=json.dumps(obj), headers=headers)
    print resp.content
    return send_text


send_text = "test%s"%random.random()
post_record(token1, send_text)

def get_up_status(uptime, token):
    headers = {
        "Content-Type":"application/json",
        "Authorization":"Bearer " + token
    }
    url = URL + "/statuses/index.json?uptime=%s"%uptime
    print url
    resp = requests.get(url, headers=headers)
    assert(resp.status_code == 200)
    obj = json.loads(resp.content)
    #print json.dumps(obj, sort_keys=True, indent=4, separators=(',', ': '))
    print send_text, obj["data"][0]["text"]


print "-------------------------"
get_up_status(latest0, token)
get_up_status(latest1, token1)


