/*
    Pirate Bulletin Board System
    Copyright (C) 1990, Edward Luke, lush@Athena.EE.MsState.EDU
    Eagles Bulletin Board System
    Copyright (C) 1992, Raymond Rocker, rocker@rock.b11.ingr.com
                        Guy Vega, gtvega@seabass.st.usm.edu
                        Dominic Tynes, dbtynes@seabass.st.usm.edu

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 1, or (at your option)
    any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
*/
/* user cache 处理
   用hask table 保存所有ID的id名,加快查找速度
   2001.5.4 KCN
*/
   

#include "bbs.h"
#include <sys/ipc.h>
#include <sys/shm.h>
#define chartoupper(c)  ((c >= 'a' && c <= 'z') ? c+'A'-'a' : c)

int     usernumber;
struct UCACHE   *uidshm;

int ucache_lock()
{
    int lockfd;
    lockfd = creat( BBSHOME "/ucache.lock", 0600 );
    if( lockfd < 0 ) {
        log_usies( "CACHE", strerror(errno) );
        return -1;
    }
    flock(lockfd,LOCK_EX);
    return lockfd;
}

int ucache_unlock(int fd)
{
    flock(fd,LOCK_UN);
    close(fd);
}


unsigned int ucache_hash(char* userid)
{
	int * i=(int*) userid;
	char* ptr;
	char key[4];
	unsigned int retkey;
	
	if (*userid==0) return 0;
	key[0]=0;
	key[1]=0;
	key[2]=0;
	key[3]=0;
        for (ptr=userid;*ptr&&(ptr-userid)<IDLEN;ptr++) {
		if (*ptr<='z'&&*ptr>='a')
			key[(ptr-userid)%4]+=*ptr&0xdf;
		else
			key[(ptr-userid)%4]+=*ptr;
	}
	retkey=(*(unsigned int*)key)%(UCACHE_HASHSIZE-1);
	return retkey+1;
}

int
fillucache(uentp) /* user cache中 添加user */
struct userec *uentp ;
{
    if(usernumber < MAXUSERS) {
    	int hashkey;
        strncpy((char*)uidshm->users[usernumber],uentp->userid,IDLEN+1) ;
        uidshm->users[usernumber][IDLEN] = '\0' ;
        hashkey = ucache_hash(uentp->userid);
        uidshm->next[usernumber] = uidshm->hashhead[hashkey];
        uidshm->hashhead[hashkey] = ++usernumber;
    }
    return 0 ;
}


void
resolve_ucache()
{
    struct stat st ;
    int         ftime;
    time_t      now;
    char   log_buf[256]; /* Leeward 99.10.24 */
	
    if( uidshm == NULL ) {
        uidshm = (struct UCACHE*)attach_shm( "UCACHE_SHMKEY_HASH", 3697, sizeof( *uidshm ) ); /*attach to user shm */
    }

    if( stat( PASSFILE,&st ) < 0 ) {
        st.st_mtime++ ;
    }
    ftime = st.st_mtime;
    if( uidshm->uptime < ftime ) {
    	int lockfd = ucache_lock();
    	if (lockfd==-1) {
    		perror("can't lock ucache");
    		exit(0);
    	}
        uidshm->uptime = ftime;
    	bzero(uidshm->hashhead,UCACHE_HASHSIZE*sizeof(int));
        usernumber = 0;
        apply_record( PASSFILE, fillucache, sizeof(struct userec) ); /*刷新user cache */
        sprintf(log_buf, "reload ucache for %d users", usernumber);
        log_usies( "CACHE", log_buf );
        uidshm->number = usernumber;
        ucache_unlock(lockfd);
    }
}

/*---	period	2000-10-20	---*/
int getuserid(char * userid, int uid)
{
    if( uid > uidshm->number || uid <= 0 ) return 0;
    strncpy(userid,(char*)uidshm->users[uid-1], IDLEN+1);
    return uid;
}

void
setuserid( num, userid ) /* 设置user num的id为user id*/
int     num;
char    *userid;
{
    if( num > 0 && num <= MAXUSERS ) {
    	int oldkey,newkey,find;
        if( num > uidshm->number )
            uidshm->number = num;
        oldkey=ucache_hash((char*)uidshm->users[ num - 1 ]);
        newkey=ucache_hash(userid);
        if (oldkey!=newkey) {
		int lockfd = ucache_lock();
	        find=uidshm->hashhead[oldkey];

	        if (find==num) uidshm->hashhead[oldkey]=uidshm->next[find-1];
	        else { /* find and remove the hash node */
	          while (uidshm->next[find-1]&&
	          	strcasecmp(uidshm->users[uidshm->next[find-1]-1],
	          		uidshm->users[ num - 1 ])) 
	      			find=uidshm->next[find-1];
	          if (!uidshm->next[find-1]) {
			if (oldkey!=0) {
		          	char log_buf[256];
		          	sprintf(log_buf,"can't find %s in hash table",uidshm->users[ num - 1 ]);
		          	log_usies("CACHE",log_buf);
		          	exit(0);
			}
	          }
	          else uidshm->next[find-1] = uidshm->next[num-1];
	        }

	        uidshm->next[num-1]=uidshm->hashhead[newkey];
	        uidshm->hashhead[newkey]=num;
		    ucache_unlock(lockfd);
        }	        
        strncpy( (char*)uidshm->users[ num - 1 ], userid, IDLEN+1 );
    }
}

int
searchnewuser() /* 找cache中 空闲的 user num */
{
    register int num, i;
    if (uidshm->hashhead[0]) return uidshm->hashhead[0];
    if (uidshm->number<MAXUSERS) return uidshm->number+1;
    return 0;
}
int
searchuser(userid)
char *userid ;
{
    register int i ;

	i = uidshm->hashhead[ucache_hash(userid)];
	while (i)
		if (!strcasecmp(userid,uidshm->users[i-1]))
			return i;
		else
			i=uidshm->next[i-1];
    return 0 ;
}

int
getuser(userid) /* 取用户信息 */
char *userid ;
{
    int uid = searchuser(userid) ;

    if(uid == 0) return 0 ;
    get_record(PASSFILE,&lookupuser,sizeof(lookupuser),uid) ;
    return uid ;
}

char *
u_namearray( buf, pnum, tag )  /* 根据tag ,生成 匹配的user id 列表 (针对所有注册用户)*/
char    buf[][ IDLEN+1 ], *tag;
int     *pnum;
{
    register struct UCACHE *reg_ushm = uidshm;
    register char       *ptr, tmp;
    register int        n, total;
    char        tagbuf[ STRLEN ];
    int         ch, num = 0;

    if( *tag == '\0' ) { /* return all user disabled */
        *pnum = uidshm->number;
        return uidshm->users[0];
    }
    for( n = 0; tag[n] != '\0'; n++ ) {
        tagbuf[ n ] = chartoupper( tag[n] );
    }
    tagbuf[ n ] = '\0';
    ch = tagbuf[0];
    total = reg_ushm->number; /* reg. user total num */
    for( n = 0; n < total; n++ ) {
        ptr = (char*)reg_ushm->users[n];
        tmp = *ptr;
        if( tmp == ch || tmp == ch - 'A' + 'a' ) /* 判断第一个字符是否相同*/
            if( chkstr( tag, tagbuf, ptr ) )
                strcpy( buf[ num++ ], ptr ); /*如果匹配, add into buf */
    }
    *pnum = num;
    return buf[0];
}


