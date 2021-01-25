# Git workflow

At present Git workflow is left to the current developers, though any working practises are noted below. 

## Resetting preprod branch (UAT)

The `preprod` branch is used for UAT testing. Due to this, it sometimes has a number of different branches deployed to 
it, not all of which we always want to go live with.

Sometimes it is necessary to reset the preprod branch (UAT) to master, so we can be sure UAT is the same as master plus 
only one branch that we want to test. 

This is done via:

```
git branch -D preprod
git push origin --delete preprod
git checkout master
git checkout -b preprod
git push --set-upstream origin preprod
```

Please note this will kick off a deployment to UAT on the change to the preprod branch.
